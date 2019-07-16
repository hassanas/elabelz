<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    design
 * @package     base_default
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
?>

<?php $_code=$this->getMethodCode() ?>
<ul class="form-list" id="payment_form_<?php echo $_code ?>" style="display:none;">
<div class="row">
<div class="col-sm-4">
<div class="credit-card clearfix">
<div class="card-sctn-black"></div>
   

    
     <div class="form-group col-xs-12">
    <li>
        <label for="<?php echo $_code ?>_cc_type" class="required"><em>*</em><?php echo $this->__('Credit Card Type') ?></label>
        <div class="input-box">
            <select id="<?php echo $_code ?>_cc_type" name="payment[cc_type]" class="required-entry validate-cc-type-select">
                <option value=""><?php echo $this->__('--Please Select--')?></option>
            <?php $_ccType = $this->getInfoData('cc_type') ?>
            <?php foreach ($this->getCcAvailableTypes() as $_typeCode => $_typeName): ?>
                <option value="<?php echo $_typeCode ?>"<?php if($_typeCode==$_ccType): ?> selected="selected"<?php endif ?>><?php echo $_typeName ?></option>
            <?php endforeach ?>
            </select>
            <span class="fake-select"></span>
        </div>
    </li>
    </div>
    
       <div class="form-group col-xs-12">
    <li id="creditcardNamecn">
        <label for="<?php echo $_code ?>_cc_owner" class="required"><em>*</em><?php echo $this->__('Name on Card') ?></label>
        <div class="input-box">

            <input type="text" title="<?php echo $this->__('Name on Card') ?>" class="input-text required-entry" id="<?php echo $_code ?>_cc_owner" name="payment[cc_owner]" value="<?php echo $this->escapeHtml($this->getInfoData('cc_owner')) ?>" />
        </div>
    </li>
   </div>
    
    
    </div>
</div>    
    
<div class="col-sm-4"> 
<div class="credit-card clearfix"> 
<div class="card-sctn-black"></div> 
<div class="form-group col-xs-12"> 
    <li id="creditcardnumbercn">
        <label for="<?php echo $_code ?>_cc_number" class="required"><em>*</em><?php echo $this->__('Credit Card Number') ?></label>
        <div class="input-box">
            <input type="text" id="<?php echo $_code ?>_cc_number" name="payment[cc_number]" title="<?php echo $this->__('Credit Card Number') ?>" class="input-text validate-cc-number validate-cc-type" value="" />
        </div>
    </li>
    
     <?php echo $this->getChildHtml() ?>
    <?php if($this->hasVerification()): ?>
    <li id="<?php echo $_code ?>_cc_type_cvv_div">
        <label for="<?php echo $_code ?>_cc_cid" class="required"><em>*</em><?php echo $this->__('Verification Number') ?></label>
        <div class="input-box">
            <div class="v-fix">
                <input type="text" title="<?php echo $this->__('Verification Number') ?>" class="input-text cvv required-entry validate-cc-cvn" id="<?php echo $_code ?>_cc_cid" name="payment[cc_cid]" value="" />
            </div>
            <a href="#" class="cvv-what-is-this"><?php echo $this->__('What is this?') ?></a>
        </div>
    </li>
    <?php endif; ?>
    </div>
    <div class="form-group col-xs-12">
    <li id="<?php echo $_code ?>_cc_type_exp_div">
        <label for="<?php echo $_code ?>_expiration" class="required"><em>*</em><?php echo $this->__('Expiration Date') ?></label>
        <div class="input-box">
            <div class="v-fix v-fix1">
                <select id="<?php echo $_code ?>_expiration" name="payment[cc_exp_month]" class="month validate-cc-exp required-entry">
                <?php $_ccExpMonth = $this->getInfoData('cc_exp_month') ?>
                <?php foreach ($this->getCcMonths() as $k=>$v): ?>
                    <option value="<?php echo $k?$k:'' ?>"<?php if($k==$_ccExpMonth): ?> selected="selected"<?php endif ?>><?php echo $v ?></option>
                <?php endforeach ?>
                </select>
                <span class="fake-select"></span>
            </div>
            <div class="v-fix v-fix2">
                <?php $_ccExpYear = $this->getInfoData('cc_exp_year') ?>
                <select id="<?php echo $_code ?>_expiration_yr" name="payment[cc_exp_year]" class="year required-entry">
                <?php foreach ($this->getCcYears() as $k=>$v): ?>
                    <option value="<?php echo $k?$k:'' ?>"<?php if($k==$_ccExpYear): ?> selected="selected"<?php endif ?>><?php echo $v ?></option>
                <?php endforeach ?>
                </select>
                <span class="fake-select"></span>
            </div>
        </div>
    </li>
    </div>
</div>   
</div>
    <div class="col-sm-4"> 
        <?php if ($this->hasSsCardType()): ?>
        <li id="<?php echo $_code ?>_cc_type_ss_div">
            <ul class="inner-form">
                <li class="form-alt"><label for="<?php echo $_code ?>_cc_issue" class="required"><em>*</em><?php echo $this->__('Switch/Solo/Maestro Only') ?></label></li>
                <li>
                    <label for="<?php echo $_code ?>_cc_issue"><?php echo $this->__('Issue Number') ?>:</label>
                    <span class="input-box">
                        <input type="text" title="<?php echo $this->__('Issue Number') ?>" class="input-text validate-cc-ukss cvv" id="<?php echo $_code ?>_cc_issue" name="payment[cc_ss_issue]" value="" />
                    </span>
                </li>
    
                <li>
                    <label for="<?php echo $_code ?>_start_month"><?php echo $this->__('Start Date') ?>:</label>
                    <div class="input-box">
                        <div class="v-fix v-fix1">
                            <select id="<?php echo $_code ?>_start_month" name="payment[cc_ss_start_month]" class="validate-cc-ukss month">
                            <?php foreach ($this->getCcMonths() as $k=>$v): ?>
                                <option value="<?php echo $k?$k:'' ?>"<?php if($k==$this->getInfoData('cc_ss_start_month')): ?> selected="selected"<?php endif ?>><?php echo $v ?></option>
                            <?php endforeach ?>
                            </select>
                            <span class="fake-select"></span>
                        </div>
                        <div class="v-fix v-fix2">
                            <select id="<?php echo $_code ?>_start_year" name="payment[cc_ss_start_year]" class="validate-cc-ukss year">
                            <?php foreach ($this->getSsStartYears() as $k=>$v): ?>
                                <option value="<?php echo $k?$k:'' ?>"<?php if($k==$this->getInfoData('cc_ss_start_year')): ?> selected="selected"<?php endif ?>><?php echo $v ?></option>
                            <?php endforeach ?>
                            </select>
                            <span class="fake-select"></span>
                        </div>
                    </div>
                </li>
                <li class="adv-container">&nbsp;</li>
            </ul>
            <script type="text/javascript">
            //<![CDATA[
            var SSChecked<?php echo $_code ?> = function() {
                var elm = $('<?php echo $_code ?>_cc_type');
                if (['SS','SM','SO'].indexOf(elm.value) != -1) {
                    $('<?php echo $_code ?>_cc_type_ss_div').show();
                } else {
                    $('<?php echo $_code ?>_cc_type_ss_div').hide();
                }
            };
    
            Event.observe($('<?php echo $_code ?>_cc_type'), 'change', SSChecked<?php echo $_code ?>);
            SSChecked<?php echo $_code ?>();
            //]]>
            </script>
        </li>
    
        <?php endif; ?>
    </div>
</div>
</ul>