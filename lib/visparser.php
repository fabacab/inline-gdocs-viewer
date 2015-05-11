<?php
/***********************************************************************
    Copyright 2008-2009 Mark Williams

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

        http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License. 

    File: visparser.php
***********************************************************************/

define("TYPE", "vp-type");
define("VALUE", "vp-value");
define("UNKNOWN", 0);
define("RESID", 1);
define("ID",2);
define("OPERATOR", 3);
define("SIMPLE", 4);
define("FUNCT", 5);
define("NUMBER", 6);
define("STRING", 7);
define("LITERAL", 8);

class visparser {
    private $reserved;
    private $keywords;
    private $result;
    private $tok;
    private $query;
    private $fields;
    private $tok_save;
    private $query_save;
    public $error_message;
    private $null;

    private $expr_cache;

    public function __construct(&$fields) {
        $this->reserved = array(
                          "select",
                          "where",
                          "group",
                          "pivot",
                          "order",
                          "by",
                          "limit",
                          "offset",
                          "label",
                          "format",
                          "options",
                          "asc",
                          "desc",
                          "true",
                          "false",
                          "and",
                          "or",
                          "not",
                          "date",
                          "timeofday",
                          "datetime",
                          "timestamp");

        $this->reserved = array_combine($this->reserved, array_keys($this->reserved));

        $this->fields =& $fields;
        $this->expr_cache = array();
        $this->null = NULL;
    }

    public function next_token()
    {
        if ($this->error_message) {
            return $this->tok;
        }

        $q = $this->query;
        $start = 0;
        while (($ch = substr($q,$start,1)) !== FALSE && ctype_space($ch)) {
            $start++;
        }

        if ($ch === FALSE) {
            $this->tok = array(TYPE => "eof");
            return FALSE;
        }

        $ret = FALSE;

        $end = $start;
        if ($ch == '`') {
            while (($ch = substr($q,++$end,1)) !== FALSE && $ch != '`')
                ;

            $ret = array(TYPE => ID, VALUE => substr($q, $start+1, $end - $start - 1));
            ++$end;
        } else if (ctype_digit($ch) || $ch == '.') {
            $ndot = $ch == '.' ? 1 : 0;
            while (($ch = substr($q,++$end,1)) !== FALSE) {
                if (!ctype_digit($ch)) {
                    if ($ndot || $ch != '.') {
                        break;
                    }
                    $ndot = 1;
                }
            }
            if ($ch == 'e' || $ch == 'E') {
                $ch = substr($q,++$end,1);
                if ($ch == '+' || $ch == '-') {
                    $ch = substr($q,++$end,1);
                }
                while (ctype_digit($ch)) {
                    $ch = substr($q,++$end,1);
                }
            }
            $ret = array(TYPE => NUMBER, VALUE => substr($q, $start, $end-$start));
        } else if (ctype_punct($ch)) {
            $end = $start + 1;
            $pos = strpos(",()=*+-/", $ch);
            if (!($pos === FALSE)) {
                $ret = array(TYPE => OPERATOR, VALUE => $ch);
                $end = $start+1;
            } else {
                $two = substr($q,$start,2);
                if ($two == "!=" || $two == "<>" || $two == "<=" || $two == ">=") {
                    $ret = array(TYPE => OPERATOR, VALUE => $two);
                    $end++;
                } else if ($ch == '<' || $ch == '>') {
                    $ret = array(TYPE => OPERATOR, VALUE => $ch);
                } else if ($ch == "'" || $ch == '"') {
                    $quote = $ch;
                    $ret = "";
                    while (($ch = substr($q,$end++,1)) !== FALSE) {
                        if ($ch == $quote) break;
                        if ($ch == "\\") {
                            $ch = substr($q,$end++,1);
                        }
                        $ret .= $ch;
                    }
                    $ret = array(TYPE => STRING, VALUE => $ret);
                } else {
                    $ret = array(TYPE => UNKNOWN);
                }
            }
        } else if (ctype_alpha($ch) || $ch == '_') {
            while (($ch = substr($q,++$end,1)) !== FALSE &&
                   (ctype_alnum($ch) || $ch == '_'))
                {
                }
            $ret = substr($q, $start, $end-$start);
            
            if (isset($this->reserved[$ret])) {
                $ret = array(TYPE => RESID, VALUE => $ret);
            } else {
                $ret = array(TYPE => ID, VALUE => $ret);
            }
        } else {
            $ret = array(TYPE => UNKNOWN);
        }

        $this->query = substr($q, $end);
        $this->tok = $ret;
        return $ret;
    }

    public function parse($q) {
        $this->query = $q;
        $this->result = array();
        $this->next_token();
  
        $this->selectClause();
        $this->whereClause();
        $this->exprListClause('group', 'by');
        $this->exprListClause('pivot');
        $this->orderByClause();
        $this->exprClause('limit');
        $this->exprClause('offset');
        $this->labelClause('label');
        $this->labelClause('format');
        $this->optionsClause();

        if ($this->tok[TYPE] != 'eof') {
            $this->error("Unexpected token '{$this->tok[VALUE]}'\n");
        }

        if ($this->error_message) {
            return FALSE;
        }

        return $this->result;
    }

    private function &getColumnTerm()
    {
        $val = $this->getColumnTermInt();
        return $this->getSharedExpr($val);
    }

    private function &getColumnExpr($prec = 0)
    {
        $val = $this->getColumnExprInt($prec);
        return $this->getSharedExpr($val);
    }

    private function &getSharedExpr($val)
    {
        $tag = $val[TYPE].":".$val[VALUE];
        if (isset($this->expr_cache[$tag])) {
            foreach ($this->expr_cache[$tag] as &$v) {
                if ($this->matchExpr($val, $v)) {
                    return $v;
                }
            }
        }
        for ($i = 0; isset($val[$i]); $i++) {
            if (isset($val[$i]['is_aggregate'])) {
                $val['is_aggregate'] = 1;
                break;
            }
        }
        $this->expr_cache[$tag][] =& $val;
        return $val;
    }

    private function getFuncTerm($id)
    {
        $ret = array(TYPE => FUNCT, VALUE => $id);
        $type = 'string';
        $this->next_token();
        if ($this->tok[TYPE] != OPERATOR || $this->tok[VALUE] != ')') {
            while (TRUE) {
                $arg =& $this->getColumnExpr();
                $type = $arg['type'];
                $ret[] =& $arg;
                if ($this->tok[TYPE] != OPERATOR || $this->tok[VALUE] != ',') {
                    break;
                }
                $this->next_token();
            }
        }
        if ($this->tok[TYPE] != OPERATOR || $this->tok[VALUE] != ')') {
            $this->error("Missing ')'");
        }
        $this->next_token();
        switch ($id) {
            case 'max':
            case 'min':
                $ret['is_aggregate'] = 1;
                break;
            case 'count':
            case 'sum':
            case 'avg':
                $ret['is_aggregate'] = 1;
            case 'year':
            case 'month':
            case 'day':
            case 'hour':
            case 'minute':
            case 'second':
            case 'millisecond':
            case 'quarter':
            case 'dayofweek':
            case 'datediff':
                $type = 'number';
                break;
            case 'now':
                $type = 'datetime';
                break;
            case 'todate':
                $type = 'date';
                break;
            case 'upper':
            case 'lower':
                $type = 'string';
                break;
            case 'datetime':
            case 'date':
            case 'timeofday':
                $type = $id;
                break;
        }
        $ret['type'] = $type;
        return $ret;
    }

    private function getColumnTermInt()
    {
        if ($this->tok[TYPE] == ID) {
            $id = $this->tok[VALUE];
            $this->next_token();
            if ($this->tok[TYPE] != OPERATOR || $this->tok[VALUE] != '(') {
                if (!isset($this->fields[$id])) {
                    $this->error("`$id' is not a known field");
                }
                $this->fields[$id]['is_used'] = TRUE;
                return $this->fields[$id];
            }
            return $this->getFuncTerm($id);
        }

        if ($this->tok[TYPE] == OPERATOR && $this->tok[VALUE] == '(') {
            $this->next_token();
            $ret = $this->getColumnExprInt();
            if ($this->tok[TYPE] != OPERATOR || $this->tok[VALUE] != ')') {
                $this->error("Missing ')': {$this->tok[TYPE]} {$this->tok[VALUE]}");
                return array(TYPE => UNKNOWN);
            }
            $this->next_token();
            return $ret;
        }

        if ($this->tok[TYPE] == NUMBER || $this->tok[TYPE] == STRING) {
            $ret = array(TYPE => LITERAL, VALUE => $this->tok[VALUE],
                         'type' => $this->tok[TYPE] == NUMBER ? 'number' : 'string');
            $this->next_token();
            return $ret;
        }

       if ($this->tok[TYPE] == RESID) {
           $v = $this->tok[VALUE];
           switch ($v) {
           case 'not':
               $this->next_token();
               $term = & $this->getColumnTerm();
               return array(TYPE => OPERATOR, VALUE => $v, 'type' => 'boolean', &$term);
           case 'true':
           case 'false':
               return array(TYPE => LITERAL, VALUE => $this->tok[VALUE] == 'true', 'type' => 'boolean');
           case 'timestamp':
               $v = 'datetime';
           case 'date':
           case 'timeofday':
           case 'datetime':
               $this->next_token();
               if ($this->tok[TYPE] == OPERATOR && $this->tok[VALUE] == '(') {
                   return $this->getFuncTerm($v);
               }
               if ($this->tok[TYPE] != STRING) break;
               $ret = array(TYPE => LITERAL, VALUE=>$this->tok[VALUE], 'type' => $v);
               $this->next_token();
               return $ret;
           }
       }

       $this->error("Unexpected token '{$this->tok[VALUE]}'");
       return array(TYPE => UNKNOWN);
    }

    private function getColumnExprInt($prec = 0)
    {
        $lhs =& $this->getColumnTerm();

        $type = isset($lhs['type']) ? $lhs['type'] : 'string';
        while ($this->tok[TYPE] == OPERATOR ||
               $this->tok[TYPE] == RESID ||
               $this->tok[TYPE] == ID)
        {
            $p2 = -1;
            $op = $this->tok[VALUE];
            switch ($op) {
                case '*':
                case '/': $p2 = 10; break;
                case '+':
                case '-': $p2 = 9; break;
                case 'starts':
                case 'ends':
                    if (8 < $prec) break;
                    $this->tok_save = $this->tok;
                    $this->query_save = $this->query;
                    $this->next_token();
                    if ($this->tok[TYPE] != ID || $this->tok[VALUE] != 'with') {
                        $this->tok = $this->tok_save;
                        $this->query = $this->query_save;
                        break;
                    }
                    $op .= "_with";
                case '<':
                case '>':
                case '<=':
                case '>=':
                case 'starts_with':
                case 'ends_with':
                case 'contains':
                case 'matches': $p2 = 8; break;
                case '=':
                case '!=':
                case '<>': $p2 = 7; break;
                case 'and': $p2 = 6; break;
                case 'or': $p2 = 5; break;
            }
            if ($p2 < $prec) break;
            if ($p2 <= 8 || $p2 == 100) $type = 'boolean';
            if ($p2 >=9 && $p2 <= 10) $type = 'number';

            $this->next_token();
            $rhs =& $this->getColumnExpr($p2);
            unset($val);
            $val = array(TYPE=>OPERATOR,VALUE=>$op,'type'=>$type,&$lhs,&$rhs);
            unset($lhs);
            $lhs =& $val;
        }
        return $lhs;
    }

    private function error($msg)
    {
        $this->tok = array(TYPE => UNKNOWN);
        if (!$this->error_message) {
            $this->error_message = $msg;
        }
    }

    private function checkType($first, $second = NULL)
    {
        if ($this->tok[TYPE] != RESID || $this->tok[VALUE] != $first) return FALSE;
        $this->next_token();
        if ($second) {
            if ($this->tok[TYPE] != RESID || $this->tok[VALUE] != $second) {
                $this->error("Missing keyword `$second'");
                return FALSE;
            }
            $this->next_token();
        }
        return TRUE;
    }

    private function matchExpr($e1, $e2)
    {
        if ($e1[TYPE] != $e2[TYPE]) return FALSE;
        if ($e1[VALUE] != $e2[VALUE]) return FALSE;
        for ($i = 0; isset($e1[$i]); $i++) {
            if (!isset($e2[$i])) return FALSE;
            if (!$this->matchExpr($e1[$i], $e2[$i])) return FALSE;
        }
        return !isset($e2[$i]);
    }

    private function &findField($f)
    {
        $len = count($this->result['select']);
        for ($i = 0; $i < $len; $i++) {
            if ($this->matchExpr($f, $this->result['select'][$i])) { 
                return $this->result['select'][$i];
            }
        }
        return $this->null;
    }

    private function selectClause()
    {
        $this->exprListClause('select');
        if (!isset($this->result['select'])) {
            $this->result['select'] = array();
            foreach ($this->fields as $name => $value) {
                $this->result['select'][] = $value;
            }
        }
    }

    private function exprListClause($first, $second = NULL)
    {
        if (!$this->checkType($first, $second)) return;
        if ($first == 'select' && $this->tok[TYPE] == OPERATOR && $this->tok[VALUE] == '*') {
            $this->next_token();
            return;
        }

        $this->result[$first] = array();
        while (true) {
            $col_id =& $this->getColumnExpr();
            $this->result[$first][] =& $col_id;
            if ($first != 'select') {
                $field =& $this->findField($col_id);
                if ($field) {
                    $field["is_".$first] = 1;
                }
            }
            if ($this->tok[TYPE] != OPERATOR || $this->tok[VALUE] != ',') {
                break;
            }
            $this->next_token();
        }
    }

    private function exprClause($first, $second = NULL)
    {
        if (!$this->checkType($first, $second)) return;
        $this->result[$first] =& $this->getColumnExpr();
    }

    static private function decompose(&$expr, &$where, &$having)
    {
        if ($expr[TYPE] == OPERATOR && $expr[VALUE] == 'and') {
            self::decompose($expr[0], $where, $having);
            self::decompose($expr[1], $where, $having);
        } else {
            if ($expr['is_aggregate']) {
                $having[] =& $expr;
            } else {
                $where[] =& $expr;
            }
        }
    }

    private function &compose($list)
    {
        $e = NULL;
        foreach ($list as &$term) {
            if (!$e) {
                $e =& $term;
            } else {
                $t = array(TYPE => OPERATOR, VALUE => 'and', 'type' => 'boolean',
                           &$e, &$term);
                unset($e);
                $e =& $this->getSharedExpr($t);
            }
        }
        return $e;
    }

    private function whereClause()
    {
        $this->exprClause('where');
        if (isset($this->result['where'])) {
            if ($this->result['where']['is_aggregate']) {
                $where = array();
                $having = array();
                self::decompose($this->result['where'],$where, $having);
                $this->result['where'] = $this->compose($where);
                $this->result['having'] = $this->compose($having);
            }
        }
    }

    private function orderByClause()
    {
        if (!$this->checkType('order', 'by')) return;
        $ix = 0;
        $this->result['order'] = array();
        while (true) {
            $col_id =& $this->getColumnExpr();
            if ($this->tok[TYPE] == RESID &&
                ($this->tok[VALUE] == 'asc' || $this->tok[VALUE] == 'desc'))
            {
                $col_id['dir'] = $this->tok[VALUE];
                $this->next_token();
            } else if (!$col_id['dir']) {
                $col_id['dir'] = 'asc';
            }
            $this->result['order'][] = &$col_id;
            if ($this->tok[TYPE] != OPERATOR || $this->tok[VALUE] != ',') {
                break;
            }
            $this->next_token();
        }
    }

    private function labelClause($label)
    {
        if (!$this->checkType($label)) return;
        while (true) {
            $col_id =& $this->getColumnExpr();
            if ($this->tok[TYPE] != STRING) {
                $this->error("Missing $label string");
                return;
            }
            $field =& $this->findField($col_id);
            if (!$field) {
                $this->error("$label names a field that does not exist");
                return;
            } else {
                $field[$label] = $this->tok[VALUE];
            }
            $this->next_token();
            if ($this->tok[TYPE] != OPERATOR || $this->tok[VALUE] != ',') {
                break;
            }
            $this->next_token();
        }
    }

    private function optionsClause()
    {
        if (!$this->checkType('options')) return;
        $this->result['options'] = array();
        while (true) {
            if ($this->tok[TYPE] != ID) {
                $this->error("Bad option");
                return;
            }
            switch ($this->tok[VALUE]) {
                case "no_format":
                case "no_values":
                    $this->result['options'][$this->tok[VALUE]] = 1;
                    break;
                default:
                    $this->error("Bad option");
                    return;
            }
            $this->next_token();
            if ($this->tok[TYPE] != OPERATOR || $this->tok[VALUE] != ',') {
                break;
            }
            $this->next_token();
        }
    }
};
