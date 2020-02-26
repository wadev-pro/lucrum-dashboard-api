<?php

namespace App\Traits;


trait UtilsTrait
{
    /**
     * @return String
     */
    protected function formatLeadString($str)
    {
        return str_replace('"','',$str);
    }

    /**
     * @param Array $arr
     * @param String $key
     * @return Array
     */
    protected function unique_multidim_array($array, $key) {
        $temp_array = array();
        $i = 0;
        $key_array = array();

        foreach($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }
        return $temp_array;
    }
}
