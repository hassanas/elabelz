<?php
/**
 * Box packing (3D bin packing, knapsack problem)
 * @package BoxPacker
 * @author Doug Wright
 */

/**
 * List of boxes available to put items into, ordered by volume
 * @author Doug Wright
 * @package BoxPacker
 */
class Infomodus_Dhllabel_Model_Packer_BoxList extends \SplMinHeap
{
    /**
     * Compare elements in order to place them correctly in the heap while sifting up.
     * @see \SplMinHeap::compare()
     */
    public function compare($boxA, $boxB)
    {
        if ($boxB->getInnerVolume() > $boxA->getInnerVolume()) {
            return 1;
        } elseif ($boxB->getInnerVolume() < $boxA->getInnerVolume()) {
            return -1;
        } else {
            return 0;
        }
    }
}
