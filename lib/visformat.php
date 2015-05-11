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

    File: visformat.php
***********************************************************************/

class NumberFormatter {
    private $pos_format;
    private $neg_format;

    private $decimal_sep;
    private $group_sep;
    private $exponent_string;
    private $negative_prefix;
    private $positive_prefix;
    private $def_first_group;
    private $def_other_group;

    const PATTERN_DECIMAL = 1;

    public function __construct($locale, $style, $format) {
        $lsave = setlocale(LC_ALL, "0");

        $this->def_first_group = 3;
        $this->decimal_sep = '.';
        $this->group_sep = ',';
        $this->exponent_string = "E";
        $this->negative_prefix = "-";
        $this->positive_prefix = "+";

        $loc = setlocale(LC_ALL, $locale);
        if ($loc) {
            $info = localeconv();

            $this->decimal_sep = $info['decimal_point'];
            $this->group_sep = $info['thousands_sep'];
            // $this->negative_prefix = $info['negative_sign'];
            // $this->positive_prefix = $info['positive_sign'];

            setlocale(LC_ALL, $lsave);
        }

        $fmts = explode(";", $format);
        if (!isset($fmts[1])) {
            $fmts[1] = $this->negative_prefix.$fmts[0];
        }

        $neg = $this->parsefmt($fmts[1]);
        $this->pos_format = $this->parsefmt($fmts[0]);

        $neg2 = $this->pos_format;
        $neg2[0] = $neg[0];
        $neg2[3] = $neg[3];
        $this->neg_format = $neg2;

        // print_r($this);
    }

    private function parsefmt($fmt)
    {
        $parts = array("","","","");
        $inquote = FALSE;
        $state = 0;
        for ($start = 0; ($ch = substr($fmt,$start,1)) !== FALSE; $start++) {
            switch ($state) {
            case '0':
            case '3':
                $is_special = strpos("0123456789@#.,", $ch) !== FALSE;
                if ($ch == '*') {
                    $parts['pad'] = substr($fmt,++$start,1);
                    $parts['pad_pos'] = $state ^ ($parts[$state] !== ""?1:0);
                    if ($parts[$state] !== "") {
                        $state++;
                    }
                } else if ($ch == "'") {
                    if (substr($fmt,$start+1,1) == "'") {
                        $parts[$state] .= "'";
                        $start += 1;
                    } else {
                        $inquote = !$inquote;
                    }
                } else if ($inquote || !$is_special) {
                    $parts[$state] .= $ch;
                } else {
                    $start--;
                    $state++;
                }
                break;
            case '1':
                if (preg_match("/^([#,]*[0-9,]+(\.0*#*)?|[#,]*[@,]+[#,]*)/", substr($fmt,$start), $matches)) {
                    $parts[1] = $matches[1];
                    $start += strlen($matches[1]) - 1;
                    $state++;
                } else {
                    $this->error = "Missing Number in format";
                }
                break;
            case 2:
                if (preg_match("/^(E(\+?)(0+))?/", substr($fmt,$start), $matches)) {
                    $parts[2] = $matches[1];
                    $parts['exp_fmt'] = "%{$matches[2]}0".strlen($matches[2].$matches[3])."d";
                    $start += strlen($matches[1]);
                }
                $state++;
                $start--;
                break;
            case 4:
                $this->error = "Unexpected text following suffix";
                break;
            }       
        }

        $parts["format_width"] = strlen($parts[0].$parts[1].$parts[2].$parts[3]);

        if (strpos($parts[0].$parts[3],"%") !== FALSE) {
            $parts["multiplier"] = 100;
        }
        if (strpos($parts[0].$parts[3],"\u2030") !== FALSE) {
            $parts["multiplier"] = 1000;
        }

        $rep = "";
        $commas = array();
        for ($i = 0; ($ch = substr($parts[1],$i,1)) !== FALSE; $i++) {
            if ($ch == ',') {
                $commas[] = $i;
            } else if ($ch == '.') {
                $rep .= substr($parts[1],$i);
                break;
            } else {
                $rep .= $ch;
            }
        }
        $at = strpos($rep, "@");
        if ($at !== FALSE) {
            // strip leading "#" chars.
            $rep = substr($rep, $at);
            $max = strlen($rep);
            $min = strpos($rep, "#");
            if ($min === FALSE) $min = $max;
            $parts['sigmax'] = $max;
            $parts['sigmin'] = $min;
        } else {
            $z = strpos($rep, '0');
            $d = strpos($rep, '.');
            if ($d === FALSE) $d = strlen($rep);

            $min_int = $d - $z;
            $max_int = $d;
            $max_flt = strlen(substr($rep, $d+1));
            $min_flt = strpos(substr($rep, $d+1), "#");
            if ($min_flt === FALSE) {
                $min_flt = $max_flt;
            }
            $parts['min_int'] = $min_int;
            $parts['max_int'] = $max_int;
            $parts['min_flt'] = $min_flt;
            $parts['max_flt'] = $max_flt;
        }
        $parts[1] = $rep;
        $len = count($commas);
        if ($len) {
            $parts['first_group'] = $i - $commas[$len-1] - 1;
            if ($len > 1) {
                $parts['second_group'] = $commas[$len-1] - $commas[$len-2] - 1;
            } else if ($parts['first_group'] == 0) {
                $parts['first_group'] = $this->def_first_group;
                if (isset($this->def_other_group)) {
                    $parts['second_group'] = $this->def_other_group;
                }
            }
        }
        return $parts;
    }

    public function format($number)
    {
        if ($number < 0) {
            $format = $this->neg_format;
            $number = -$number;
        } else {
            $format = $this->pos_format;
        }

        if ($format['multiplier']) {
          $number *= $format['multiplier'];
        }
        if ($format[2]) {
            $exp_group = 1;
            if (isset($format['sigmax'])) {
                $max_int = $min_int = 1;
                $max_flt = $min_flt = $format['sigmax'] - 1;
            } else {
                $min_int = $format['min_int'];
                $max_int = $format['max_int'];
                $min_flt = $format['min_flt'];
                $max_flt = $format['max_flt'];
            }
            if ($max_int > $min_int) {
                $min_int = 1;
                $exp_group = $max_int;
            }
            $dig = $min_int + $max_flt - 1;
            $s = sprintf("%.{$dig}e", $number);
            if (preg_match("/^([0-9]+)\.([0-9]*)E(.*)$/i", $s, $matches)) {
                $intval = $matches[1].$matches[2];
                $exp = intval($matches[3]) + strlen($matches[1]) - $min_int;
                $adj = $exp % $exp_group;
                $split = $min_int;
                if ($adj < 0) {
                    $adj = $exp_group + $adj;
                }
                $split += $adj;
                $exp -= $adj;
                    
                $int = substr($intval, 0, $split);
                $flt = substr($intval, $split);
                
                while (substr($flt,-1) == "0" && strlen($flt) > $min_flt) {
                    $flt = substr($flt, 0, -1);
                }
                $e = sprintf($format['exp_fmt'], $exp);
                $sgn = substr($e,0,1);
                if ($sgn == '+' || $sgn == '-') {
                    $e = ($sgn == '+' ? $this->positive_prefix : $this->negative_prefix).
                        substr($e,1);
                }
                $s = "$int.$flt".$this->exponent_string.$e;
            }
        } else if (isset($format['sigmax'])) {
            $dig = strlen($format[1])-1;
            $s = sprintf("%.{$dig}e", $number);
            $max = $format['sigmax'];
            $min = $format['sigmin'];
            if (preg_match("/^([0-9])\.([0-9]*)E(.*)$/i", $s, $matches)) {
                $intval = $matches[1].$matches[2];
                $exp = intval($matches[3])+1;
                $len = strlen($intval);
                while ($len > $min && substr($intval, -1)=='0') {
                    $intval = substr($intval, 0, --$len);
                }
                if ($exp >= $len) {
                    $intval = str_pad($intval, $exp, "0");
                } else if ($exp < 0) {
                    $intval = "0." + str_pad("", -$exp, "0").$intval;
                } else {
                    $intval = substr($intval, 0, $exp).".".substr($intval, $exp);
                }
                $s = $intval;
            }
        } else {
            $min_int = $format['min_int'];
            $max_int = $format['max_int'];
            $min_flt = $format['min_flt'];
            $max_flt = $format['max_flt'];

            $s = sprintf("%.{$max_flt}F", $number);
            $d = strpos($s, ".");
            if ($d === FALSE) $d = strlen($s);
            if ($d < min_int) {
                $s = str_pad("", $min_int - $d, "0").$s;
            }
            while (substr($s, -1) == "0" && strlen($s)-$d-1 > $min_flt) {
                $s = substr($s, 0, -1);
            }
        }
        $d = strpos($s, ".");
        if ($d === FALSE) {
            $t = $s;
            $s = "";
        } else {
            $t = substr($s, 0, $d);
            $s = $this->decimal_sep . substr($s, $d + 1);
        }
        if (isset($format["first_group"])) {
            $g = $format["first_group"];
            $g2 = isset($format["second_group"]) ? $format["second_group"] : $g;
            while (strlen($t) > $g) {
                $s = $this->group_sep . substr($t, -$g) . $s;
                $t = substr($t,0,-$g);
                $g = $g2;
            }
        }
        $s = $t . $s;
        $t = $format[0] . $s . $format[3];
        $len = strlen($t);
        if (isset($format['pad'])) {
            $plen = $format['format_width'];
            if ($plen > $len) {
                $pad = str_pad("", $plen - $len, $format['pad']);
                if (!($format['pad_pos'] & 1)) {
                    $s = $t;
                }
                if ($format['pad_pos'] & 2) {
                    $s = $s.$pad;
                } else {
                    $s = $pad.$s;
                }
                if ($format['pad_pos'] & 1) {
                    $s = $format[0] . $s . $format[3];
                }
            } else {
                $s = $t;
            }
        } else {
            $s = $t;
        }

        return $s;
    }
};

class DateFormatter {
    private $format;
    private $locale;
    private $timezone;
    private $gmt_offset;
    private $abbrev;

    public function __construct($locale, $tz, $format) {
        $this->locale = $locale;
        if (preg_match('/^%([A-Z]+):(.*)$/',$format,$matches)) {
            $tz = $matches[1];
            $format = $matches[2];
        }
        $this->format = $format;
        $this->timezone = new DateTimeZone($tz);
        $date = new DateTime("", $this->timezone);
        $this->gmt_offset = $this->timezone->getOffset($date);
        $abbrev = timezone_abbreviations_list();
        $this->abbrev = $tz;
        if (!isset($abbrev[strtolower($tz)])) {
            $id = $this->timezone->getName();
            foreach ($abbrev as $key => $value) {
                if ($value['timezone_id'] == $id) {
                    $this->abbrev = $key;
                    break;
                }
            }
        }
    }

    public function format($date)
    {
        $date += $this->gmt_offset;

        $result = "";
        $prev = -1;
        $cur_count = 0;
        $in_quote = 0;
        $format = $this->format;
        for ($i = 0; ($ch = substr($format, $i, 1)) !== FALSE; $i++) {
            if ($ch == "'") {
                if (substr($format, $i+1, 1) === "'") {
                    $prev = -1;
                    $result .= $ch;
                } else {
                    $in_quote = !$in_quote;
                }
            } else if (!$in_quote && ctype_alpha($ch)) {
                if ($prev == $ch) {
                    $cur_count++;
                } else {
                    $result .= $this->convert($date, $prev, $cur_count);
                    $prev = $ch;
                    $cur_count = 1;
                }
            } else {
                if ($prev !== -1) {
                    $result .= $this->convert($date, $prev, $cur_count);
                    $prev = -1;
                }
                $result .= $ch;
            }
        }
        $result .= $this->convert($date, $prev, $cur_count);
        return $result;
    }

    private function convert($date, $char, $count)
    {
        $pat = "";
        if ($char === -1) return;

        switch ($char) {
        case 'G': //        era designator          (Text)              AD
            return "AD";
        case 'y': //       year                    (Number)            1996
            $pat = $count == 2 ? "%y" : "%Y"; 
            break;
        case 'M': //        month in year           (Text & Number)     July & 07
            $pat = $count >= 3 ? $count >= 4 ? "%B" : "%b" : "%m";
            break;
        case 'd': //        day in month            (Number)            10
            $pat = "%d";
            break;
        case 'h': //        hour in am/pm (1~12)    (Number)            12
            $pat = "%I";
            break;
        case 'H': //        hour in day (0~23)      (Number)            0
            $pat = "%H";
            if ($count > 2) {
                $pat = (string)(int)floor($date/3600);
                $c = strlen($pat);
                if ($c > $count) $c = $count;
                $pat = substr($pat, -$c);
                $pat = str_pad($pat, $count, " ", STR_PAD_LEFT);
            }
            break;
        case 'm': //        minute in hour          (Number)            30
            $pat = "%M";
            break;
        case 's': //        second in minute        (Number)            55
            $pat = "%S";
            break;
        case 'S': //        fractional second       (Number)            978
            return str_pad("", $count, "0");
        case 'E': //        day of week             (Text)              Tuesday
            $pat = $count >= 4 ? "%A" : "%a";
            break;
        case 'e': //*       day of week (local 1~7) (Text & Number)     Tuesday & 2
            $pat = $count >= 3 ? $count >= 4 ? "%A" : "%a" : "%d";
            break;
        case 'D': //        day in year             (Number)            189
            $pat = "%j";
            break;
        case 'F': //        day of week in month    (Number)            2 (2nd Wed in July)
            $mday = intval(gmstrftime("%d", $date));
            $pat = (string)intval(($mday + 6) / 7);
            break;
        case 'w': //        week in year            (Number)            27
            $pat = "%V";
            break;
        case 'W': //        week in month           (Number)            2
            $mday = intval(gmstrftime("%d", $date));
            $wday = intval(gmstrftime("%w", $date));
            $pat = (string)intval(($mday - $wday) / 7);
            break;
        case 'a': //        am/pm marker            (Text)              PM
            $pat = "%p";
            break;
        case 'k': //        hour in day (1~24)      (Number)            24
            $hour = intval(gmstrftime("%H", $date));
            if (!$hour) $hour = 24;
            $pat = (string)$hour;
            break;
        case 'K': //        hour in am/pm (0~11)    (Number)            0
            $hour = intval(gmstrftime("%I", $date));
            if ($hour == 12) $hour = 0;
            $pat = (string)$hour;
            break;
        case 'z': //        time zone               (Text)              Pacific Standard Time
            $pat = $this->abbrev;
            break;
        case 'Z': //        time zone (RFC 822)     (Number)            -0800
            $pat = sprintf("%+03d00", (int)(round($this->gmt_offset / 3600)));
            break;
        case 'v': //        time zone (generic)     (Text)              Pacific Time
            $pat = $this->timezone->getName();
            break;
        case 'V': //        time zone (location)    (Text)              United States (Los Angeles)
            $pat = $this->timezone->getName();
            $pat = preg_replace("%^(.*)/(.*)$%", "$1 ($2)", $pat);
            break;
        }
        if (substr($pat,0,1) == "%") {
            $lsave = setlocale(LC_ALL, "0");
            setlocale(LC_ALL, $this->locale);
            $pat = gmstrftime($pat, $date);
            setlocale(LC_ALL, $lsave);
        }
        $len = strlen($pat);
        if ($len < $count) {
            if (strpos("0123456789", substr($pat,0,1)) !== FALSE) {
                $pat = str_pad($pat, $count, "0", STR_PAD_LEFT);
            } else {
                $pat = str_pad($pat, $count, " ");
            }
        }
        return $pat;
    }


};

class BoolFormatter {
    private $format;

    public function __construct($format)
    {
        $this->format = explode(":",$format,2);
    }

    public function format($value)
    {
        return $this->format[$value?1:0];
    }
};
