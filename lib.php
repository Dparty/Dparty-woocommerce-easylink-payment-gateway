<?php

function utf8($s)
{
  return mb_convert_encoding($s, 'UTF-8');
}

function planMap($payload)
{
  $stock = array();
  foreach ($payload as $key => $value) {
    $ukey = utf8($key);
    mb_convert_encoding(stringA($payload), "UTF-8");
    if (gettype($value) == "array") {
      $stock = array_merge($stock, planMap($value));
    } else {
      $uvalue = utf8($value);
      $stock[$ukey] = utf8($uvalue);
    }
  }
  return $stock;
}

function stringA($payload)
{
  $stringList = array();
  ksort($payload);
  foreach ($payload as $key => $value) {
    array_push($stringList, $key . "=" . $value);
  }
  return join("&", $stringList);
}

function sign($payload, $key)
{
  // return "1234";
  return hash('sha256', stringA($payload) . "&" . $key);
}

function ascendingOrderMap($payload)
{
  ksort($payload);
}
