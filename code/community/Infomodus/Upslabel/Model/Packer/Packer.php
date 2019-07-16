<?php
/**
 * Box packing (3D bin packing, knapsack problem)
 * @package BoxPacker
 * @author Doug Wright
 */


/**
 * Actual packer
 * @author Doug Wright
 * @package BoxPacker
 */
class Infomodus_Upslabel_Model_Packer_Packer
{
    /**
     * List of items to be packed
     * @var ItemList
     */
    protected $items;

    /**
     * List of box sizes available to pack items into
     * @var BoxList
     */
    protected $boxes;

    public $isError= false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->items = new Infomodus_Upslabel_Model_Packer_ItemList();
        $this->boxes = new Infomodus_Upslabel_Model_Packer_BoxList();

    }

    /**
     * Add item to be packed
     * @param Item $item
     * @param int  $qty
     */
    public function addItem(Infomodus_Upslabel_Model_Packer_Item $item, $qty = 1)
    {
        for ($i = 0; $i < $qty; $i++) {
            $this->items->insert($item);
        }
    }

    /**
     * Set a list of items all at once
     * @param \Traversable|array $items
     */
    public function setItems($items)
    {
        if ($items instanceof Infomodus_Upslabel_Model_Packer_ItemList) {
            $this->items = clone $items;
        } else {
            $this->items = new Infomodus_Upslabel_Model_Packer_ItemList();
            foreach ($items as $item) {
                $this->items->insert($item);
            }
        }
    }

    /**
     * Add box size
     * @param Box $box
     */
    public function addBox(Infomodus_Upslabel_Model_Packer_Box $box)
    {
        $this->boxes->insert($box);
        Mage::log("added box {$box->getReference()}");
    }

    /**
     * Add a pre-prepared set of boxes all at once
     * @param BoxList $boxList
     */
    public function setBoxes(Infomodus_Upslabel_Model_Packer_BoxList $boxList)
    {
        $this->boxes = clone $boxList;
    }

    /**
     * Pack items into boxes
     *
     * @throws \RuntimeException
     * @return PackedBoxList
     */
    public function pack()
    {
        $packedBoxes = $this->doVolumePacking();

        //If we have multiple boxes, try and optimise/even-out weight distribution
        if ($packedBoxes->count() > 1) {
            $redistributor = new Infomodus_Upslabel_Model_Packer_WeightRedistributor($this->boxes);
            $packedBoxes = $redistributor->redistributeWeight($packedBoxes);
        }
        return $packedBoxes;
    }

    /**
     * Pack items into boxes using the principle of largest volume item first
     *
     * @throws \RuntimeException
     * @return PackedBoxList
     */
    public function doVolumePacking()
    {

        $packedBoxes = new Infomodus_Upslabel_Model_Packer_PackedBoxList;

        //Keep going until everything packed
        while ($this->items->count()) {
            $boxesToEvaluate = clone $this->boxes;
            $packedBoxesIteration = new Infomodus_Upslabel_Model_Packer_PackedBoxList;

            //Loop through boxes starting with smallest, see what happens
            while (!$boxesToEvaluate->isEmpty()) {
                $box = $boxesToEvaluate->extract();

                $volumePacker = new Infomodus_Upslabel_Model_Packer_VolumePacker($box, clone $this->items);
                $packedBox = $volumePacker->pack();
                if ($packedBox->getItems()->count()) {
                    $packedBoxesIteration->insert($packedBox);

                    //Have we found a single box that contains everything?
                    if ($packedBox->getItems()->count() === $this->items->count()) {
                        break;
                    }
                }
            }

            //Check iteration was productive
            if ($packedBoxesIteration->isEmpty()) {
                Mage::getSingleton('adminhtml/session')->addError('The weight of one product in shipment is exceed the max limit of weight box.');
                /*throw new \RuntimeException('Item ' . $this->items->top()->getDescription() . ' is too large to fit into any box');*/
                $this->isError = true;
                return $packedBoxes;
            }

            //Find best box of iteration, and remove packed items from unpacked list
            $bestBox = $packedBoxesIteration->top();
            $unPackedItems = $this->items->asArray();
            foreach (clone $bestBox->getItems() as $packedItem) {
                foreach ($unPackedItems as $unpackedKey => $unpackedItem) {
                    if ($packedItem === $unpackedItem) {
                        unset($unPackedItems[$unpackedKey]);
                        break;
                    }
                }
            }
            $unpackedItemList = new Infomodus_Upslabel_Model_Packer_ItemList();
            foreach ($unPackedItems as $unpackedItem) {
                $unpackedItemList->insert($unpackedItem);
            }
            $this->items = $unpackedItemList;
            $packedBoxes->insert($bestBox);

        }

        return $packedBoxes;
    }
}
