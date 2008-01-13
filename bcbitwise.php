<?php

// Bitwise math of arbitrary precision numbers.
// From http://cct.me.ntut.edu.tw/chchting/aiahtm/computer/phphelp/ref.bc.php.htm

// MAX_BASE is the maximum base that can be represented in
// one byte on the host machine. On most modern systems, this
// value can be 256, but if there are still any systems with 7-bit
// bytes out there, you should use 128 for maximum
// portability.

define('MAX_BASE', 256);

//// INTERFACE ROUTINES:

// Bitwise AND

function bcand($x, $y)
{
        return _bcbitwise_internal($x, $y, '_bcand');
}

// Bitwise OR

function bcor($x, $y)
{
        return _bcbitwise_internal($x, $y, '_bcor');
}

// Bitwise XOR

function bcxor($x, $y)
{
        return _bcbitwise_internal($x, $y, '_bcxor');
}

// Left shift (<<)

function bcleftshift($num, $shift)
{
        return bcmul($num, bcpow(2, $shift), 0);
}

// Right shift (>>)

function bcrightshift($num, $shift)
{
        return bcdiv($num, bcpow(2, $shift), 0);
}

// Convert decimal to hex (like PHP's builtin dechex)

function bcdechex($num)
{
  $res = "";
  while ($num != '0') {
    $byte = bcand($num, 255);
    $hex = dechex($byte);
    if (strlen($hex) == 1) $hex = '0' . $hex;
    $res = $hex . $res;
    $num = bcrightshift($num, 8);
  }
  if ($res == '') $res = 0;
  return $res;
}

// Convert hex to decimal (like PHP's builtin hexdec)

function bchexdec($hex) {
  $res = 0;
  for ($i=0; $i<strlen($hex); $i++) {
    $res = bcadd(bcleftshift($res, 4), hexdec($hex[$i]));
  }
  return $res;
}


//// INTERNAL ROUTINES

// These routines operate on only one byte. They are used to
// implement _bcbitwise_internal.

function _bcand($x, $y)
{
        return $x & $y;
}

function _bcor($x, $y)
{
        return $x | $y;
}

function _bcxor($x, $y)
{
        return $x ^ $y;
}

// _bcbitwise_internal - The majority of the code that implements
//                       the bitwise functions bcand, bcor, and bcxor.
//
// arguments           - $x and $y are the operands (in decimal format),
//                       and $op is the name of one of the three
//                       internal functions, _bcand, _bcor, or _bcxor.
//
//
// see also            - The interfaces to this function: bcand, bcor,
//                       and bcxor

function _bcbitwise_internal($x, $y, $op)
{
        $bx = bc2bin($x);
        $by = bc2bin($y);

        // Pad $bx and $by so that both are the same length.

        equalbinpad($bx, $by);

        $ix=0;
        $ret = '';

        for($ix = 0; $ix < strlen($bx); $ix++)
        {
                $xd = substr($bx, $ix, 1);
                $yd = substr($by, $ix, 1);
                $ret .= call_user_func($op, $xd, $yd);
        }

        return bin2bc($ret);
}

// equalbinpad - Pad the operands on the most-significant end
//               so they have the same number of bytes.
//
// arguments   - $x and $y, binary-format numbers (converted
//               from decimal format with bc2bin()), passed
//               by reference.
//
// notes       - Both operands are modified by this function.

function equalbinpad(&$x, &$y)
{
        $xlen = strlen($x);
          $ylen = strlen($y);

        $length = max($xlen, $ylen);
          fixedbinpad($x, $length);
        fixedbinpad($y, $length);
}

// fixedbinpad - Pad a binary number up to a certain length
//
// arguments   - $num: The operand to be padded.
//
//             - $length: The desired minimum length for
//                        $num
//
// notes       - $num is modified by this function.

function fixedbinpad(&$num, $length)
{
        $pad = '';
        for($ii = 0; $ii < $length-strlen($num); $ii++)
        {
                $pad .= bc2bin('0');
        }

        $num = $pad . $num;
}

// bc2bin       - Convert a decimal number to the internal
//                binary format used by this library.
//
// return value - The binary representation of $num.

function bc2bin($num)
{
        return dec2base($num, MAX_BASE);
}

// bin2bc       - Reverse of bc2bin

function bin2bc($num)
{
        return base2dec($num, MAX_BASE);
}

// convert a decimal value to any other base value
function dec2base($dec,$base,$digits=FALSE) {
    if($base<2 or $base>256) die("Invalid Base: ".$base);
    bcscale(0);
    $value="";
    if(!$digits) $digits=digits($base);
    while($dec>$base-1) {
        $rest=bcmod($dec,$base);
        $dec=bcdiv($dec,$base);
        $value=$digits[$rest].$value;
    }
    $value=$digits[intval($dec)].$value;
    return (string) $value;
}

// convert another base value to its decimal value
function base2dec($value,$base,$digits=FALSE) {
    if($base<2 or $base>256) die("Invalid Base: ".$base);
    bcscale(0);
    if($base<37) $value=strtolower($value);
    if(!$digits) $digits=digits($base);
    $size=strlen($value);
    $dec="0";
    for($loop=0;$loop<$size;$loop++) {
        $element=strpos($digits,$value[$loop]);
        $power=bcpow($base,$size-$loop-1);
        $dec=bcadd($dec,bcmul($element,$power));
    }
    return (string) $dec;
}

function digits($base) {
    if($base>64) {
        $digits="";
        for($loop=0;$loop<256;$loop++) {
            $digits.=chr($loop);
        }
    } else {
        $digits ="0123456789abcdefghijklmnopqrstuvwxyz";
        $digits.="ABCDEFGHIJKLMNOPQRSTUVWXYZ-_";
    }
    $digits=substr($digits,0,$base);
    return (string) $digits;
}

?>
