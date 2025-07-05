<?php
namespace Magento\Framework\App;
interface CacheInterface {
    public function load($id);
    public function save($data, $id, array $tags = [], $lifeTime = null);
    public function remove($id);
}
?>
