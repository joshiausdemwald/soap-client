<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 23.10.13
 * Time: 16:16
 */

namespace Phpforce\SoapClient;

use Phpforce\SoapClient\Result\RecordIterator;

class PartnerClient extends Client
{
    /**
     * @param object $object
     *
     * @return object $object
     */
    public function sfToPhp($object)
    {
        if($object instanceof Result\QueryResult)
        {
            return new RecordIterator($this, $object);
        }
        elseif(is_object($object))
        {
            $object->Id = $object->Id[0];

            if(isset($object->any))
            {
                $this->cleanupAnyXml($object);
            }
        }
        return $object;
    }

    /**
     * @param object $object
     */
    public function cleanupAnyXml($object)
    {
        $any = (array)$object->any;

        foreach($any AS $name => $value)
        {
            // atomic fields, parse XML!
            if(is_string($value))
            {
                $xml = <<<EOT
<any
    targetNamespace="urn:partner.soap.sforce.com"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:sf='urn:partner.soap.sforce.com'>$value</any>

EOT;
                $element = new \SimpleXMLElement($xml);

                foreach($element->children('sf', true) AS $key => $value)
                {
                    /** @var $value \SimpleXMLElement */
                    $attrs = $value->attributes('xsi', true);

                    if(isset($attrs['nil']) && (string)$attrs['nil'] === 'true')
                    {
                        $val = null;
                    }
                    else
                    {
                        $val = (string)$value;
                    }
                    $object->$key = $val;
                }
            }
            else
            {
                $object->$name = $this->sfToPhp($value);
            }
        }
        unset($object->any);
    }
} 