<?php
namespace Magento\Framework\Model;
abstract class AbstractModel implements \ArrayAccess {
    protected function _init($model){ }
    public function getId(){ return $this->getData('entity_id'); }
    public function setId($id){ return $this->setData('entity_id', $id); }
    public function offsetExists($offset){return isset($this->$offset);}
    public function offsetGet($offset){return $this->$offset;}
    public function offsetSet($offset,$value){$this->$offset=$value;}
    public function offsetUnset($offset){unset($this->$offset);}
    public function getData($key = ''){return $this->$key ?? null;}
    public function setData($key,$value){$this->$key=$value; return $this;}
}
?>
