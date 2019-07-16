<?php

/**
 *
 * @category   Progos
 * @package    Progos_ProductAttributes
 * @author     Hassan Ali Shahzad (hassan.ali@progos.org)
 * Date:       02-05-2018
 *
 */

$installer = $this;
$installer->startSetup();

$installer->removeAttribute('catalog_product', 'personal_style');
$installer->addAttribute("catalog_product", "personal_style", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Personal Style",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Athletic',
            1 => 'Bohemian',
            2 => 'Glamorous',
            3 => 'Modest',
            4 => 'Preppy',
            5 => 'Trendy',
            6 => 'Urban'
        )
    )
));


$installer->removeAttribute('catalog_product', 'top_length');
$installer->addAttribute("catalog_product", "top_length", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Top length",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Cropped',
            1 => 'Hip',
            2 => 'Long',
            3 => 'Waist'
        )
    )

));

$installer->removeAttribute('catalog_product', 'sleeve_length');
$installer->addAttribute("catalog_product", "sleeve_length", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Sleeve Length",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Elbow Length',
            1 => 'Long Sleeve',
            2 => 'Short Sleeve',
            3 => 'Sleeveless',
            4 => 'Three Quarter'
        )
    )

));

$installer->removeAttribute('catalog_product', 'jewelry_tone');
$installer->addAttribute("catalog_product", "jewelry_tone", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Jewelry Tone",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Gold',
            1 => 'Mix & Match',
            2 => 'Rose Gold',
            3 => 'Silver'
        )
    )

));

$installer->removeAttribute('catalog_product', 'jewelry_style');
$installer->addAttribute("catalog_product", "jewelry_style", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Jewelry Style",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Classic',
            1 => 'Trendy'
        )
    )

));

$installer->removeAttribute('catalog_product', 'shorts_rise');
$installer->addAttribute("catalog_product", "shorts_rise", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Shorts Rise",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'High',
            1 => 'Low',
            2 => 'Mid'
        )
    )

));

$installer->removeAttribute('catalog_product', 'shorts_length');
$installer->addAttribute("catalog_product", "shorts_length", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Shorts Length",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Knee Length',
            1 => 'Mid Thigh',
            2 => 'Mini'
        )
    )

));

$installer->removeAttribute('catalog_product', 'jeans_cut');
$installer->addAttribute("catalog_product", "jeans_cut", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Jeans Cut",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Bootcut',
            1 => 'Boyfriend',
            2 => 'Flared',
            3 => 'Skinny',
            4 => 'Straight'
        )
    )

));

$installer->removeAttribute('catalog_product', 'jeans_rise');
$installer->addAttribute("catalog_product", "jeans_rise", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Jeans Rise",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'High',
            1 => 'Low',
            2 => 'Mid'
        )
    )

));


$installer->removeAttribute('catalog_product', 'jeans_style');
$installer->addAttribute("catalog_product", "jeans_style", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Jeans Style",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Plain',
            1 => 'Print/Embroidered',
            2 => 'Ripped',
            3 => 'Straight',
            4 => 'Stretch',
            5 => 'Washed'
        )
    )

));

$installer->removeAttribute('catalog_product', 'maternity_trousers_type');
$installer->addAttribute("catalog_product", "maternity_trousers_type", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Maternity Trousers Type",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Over Belly',
            1 => 'Under Belly'
        )
    )

));

$installer->removeAttribute('catalog_product', 'heel_height');
$installer->addAttribute("catalog_product", "heel_height", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Heel Height",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'High Heel',
            1 => 'Low Heel',
            2 => 'Mid Heel',
            3 => 'No Heel',
            4 => 'Ultra High Heel'
        )
    )
));

$installer->removeAttribute('catalog_product', 'pattern');
$installer->addAttribute("catalog_product", "pattern", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Pattern",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Animal',
            1 => 'Camo',
            2 => 'Floral',
            3 => 'Geometric',
            4 => 'Novelty',
            5 => 'Paisley',
            6 => 'Plaid',
            7 => 'Plain',
            8 => 'Polka Dot',
            9 => 'Striped'
        )
    )
));

$installer->removeAttribute('catalog_product', 'fit_for_tops');
$installer->addAttribute("catalog_product", "fit_for_tops", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Fit For Tops",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Fitted',
            1 => 'Loose',
            2 => 'Oversized',
            3 => 'Straight',
            4 => 'Wide'
        )
    )
));

$installer->removeAttribute('catalog_product', 'fit_for_bottoms');
$installer->addAttribute("catalog_product", "fit_for_bottoms", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Fit For Bottoms",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Skinny',
            1 => 'Straight',
            2 => 'Tapered',
            3 => 'Wide'
        )
    )
));

$installer->removeAttribute('catalog_product', 'dress_style');
$installer->addAttribute("catalog_product", "dress_style", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Dress Style",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'A Line Dresses',
            1 => 'Bandeau Dresses',
            2 => 'Bodycon Dresses',
            3 => 'Peplum Dresses',
            4 => 'Shift Dresses',
            5 => 'Shirts Dresses',
            6 => 'Skater Dresses',
            7 => 'T-Shirt Dresses',
            8 => 'Waistline Dresses',
            9 => 'Wrap Dresses'
        )
    )
));

$installer->removeAttribute('catalog_product', 'dress_length');
$installer->addAttribute("catalog_product", "dress_length", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Dress Length",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Maxi',
            1 => 'Midi',
            2 => 'Mini'
        )
    )
));

$installer->removeAttribute('catalog_product', 'skirt_style');
$installer->addAttribute("catalog_product", "skirt_style", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Skirt Style",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'A Line',
            1 => 'Full',
            2 => 'Pencil',
            3 => 'Straight',
            4 => 'Tube'
        )
    )
));

$installer->removeAttribute('catalog_product', 'skirt_style');
$installer->addAttribute("catalog_product", "skirt_length", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Skirt Length",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Maxi',
            1 => 'Midi',
            2 => 'Mini'
        )
    )
));

$installer->removeAttribute('catalog_product', 'maternity_nursing');
$installer->addAttribute("catalog_product", "maternity_nursing", array(
    "type" => "int",
    "backend" => "",
    "frontend" => "",
    "label" => "Maternity Nursing",
    "input" => "select",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => false,
    "unique" => false,
    "note" => "",
    'option' => array(
        "values" => array(
            0 => 'Nursing',
            1 => 'Not Nursing'
        )
    )

));

$installer->endSetup();
	 