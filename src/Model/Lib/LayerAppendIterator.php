<?php


namespace Stacks\Model\Lib;


class LayerAppendIterator extends \AppendIterator implements \Countable
{

    public function count()
    {
        return iterator_count($this);
    }
}
