<?php

namespace packages\base\DB;

class Parenthesis
{
    protected $_where = [];

    /**
     * @return $this
     */
    public function where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND'): self
    {
        if (is_array($whereValue) && ($key = key($whereValue)) != '0') {
            $operator = $key;
            $whereValue = $whereValue[$key];
        }

        if (0 == count($this->_where)) {
            $cond = '';
        }
        if ('contains' == $operator) {
            $whereValue = '%'.$whereValue.'%';
            $operator = 'LIKE';
        } elseif ('equals' == $operator) {
            $whereValue = $whereValue;
            $operator = '=';
        } elseif ('startswith' == $operator) {
            $whereValue = $whereValue.'%';
            $operator = 'LIKE';
        }
        $this->_where[] = [$cond, $whereProp, $operator, $whereValue];

        return $this;
    }

    /**
     * @return $this
     */
    public function orWhere($whereProp, $whereValue = 'DBNULL', $operator = '='): self
    {
        return $this->where($whereProp, $whereValue, $operator, 'OR');
    }

    public function getWheres(): array
    {
        return $this->_where;
    }

    public function isEmpty(): bool
    {
        return empty($this->_where);
    }
}
