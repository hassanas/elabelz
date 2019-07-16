<?php

class VES_AdvancedPdfProcessor_Model_Math extends Varien_Object
{
	/**
	 * Compare two value
	 * @param unknown_type $a
	 * @param unknown_type $b
	 * @param unknown_type $operator
	 * @return boolean
	 */
    static public function compare($a, $b,$operator){
    	switch ($operator){
    		case '==':
    			return $a == $b;
    		case '===':
    			return $a === $b;
    		case '!=':
    			return $a != $b;
    		case '<>':
    			return $a <> $b;
    		case '!==':
    			return $a !== $b;
    		case '>':
    			return $a > $b;
    		case '<':
    			return $a < $b;
    		case '>=':
    			return $a >= $b;
    		case '<=':
    			return $a <= $b;
    		case '+':
    			return $a + $b;
    		case '-':
    			return $a - $b;
    		case '*':
    			return $a * $b;
    		case '/':
    			return $a / $b;
    		case '%':
    			return $a % $b;
    	}
    }
}