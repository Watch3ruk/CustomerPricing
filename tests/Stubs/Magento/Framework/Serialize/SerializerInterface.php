<?php
namespace Magento\Framework\Serialize;
interface SerializerInterface {
    public function serialize($data);
    public function unserialize($string);
}
?>
