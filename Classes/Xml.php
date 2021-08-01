<?php
namespace SimpleLab;

class Xml {
    public static function array_to_xml( $data, &$xml_data, $get_key = 'order' )
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = $get_key; //dealing with <0/>..<n/> issues
            }
            if (is_array($value)) {
                $subnode = $xml_data->addChild($key);
                self::array_to_xml($value, $subnode, $get_key);
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }
}