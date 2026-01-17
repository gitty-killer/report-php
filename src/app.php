<?php
$FIELDS = ["section", "metric", "value"];
$NUMERIC_FIELD = "value";
$STORE_PATH = "data/store.txt";

function parse_kv($items, $FIELDS) {
  $record = [];
  foreach ($items as $item) {
    $parts = explode("=", $item, 2);
    if (count($parts) !== 2) { throw new Exception("Invalid item: $item"); }
    [$key, $value] = $parts;
    if (!in_array($key, $FIELDS)) { throw new Exception("Unknown field: $key"); }
    if (strpos($value, "|") !== false) { throw new Exception("Value may not contain '|' "); }
    $record[$key] = $value;
  }
  foreach ($FIELDS as $f) if (!isset($record[$f])) $record[$f] = "";
  return $record;
}

function format_record($values, $FIELDS) {
  $parts = [];
  foreach ($FIELDS as $k) $parts[] = $k . "=" . ($values[$k] ?? "");
  return implode("|", $parts);
}

function parse_line($line) {
  $values = [];
  foreach (explode("|", trim($line)) as $part) {
    if ($part === "") continue;
    $kv = explode("=", $part, 2);
    if (count($kv) !== 2) throw new Exception("Bad part: $part");
    $values[$kv[0]] = $kv[1];
  }
  return $values;
}

function load_records($path) {
  if (!file_exists($path)) return [];
  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  return array_map('parse_line', $lines);
}

function append_record($path, $values, $FIELDS) {
  if (!is_dir("data")) mkdir("data");
  file_put_contents($path, format_record($values, $FIELDS) . "
", FILE_APPEND);
}

function summary($records, "value") {
  $count = count($records);
  if ("value" === null) return "count=$count";
  $total = 0;
  foreach ($records as $r) { $total += intval($r["value"] ?? 0); }
  return "count=$count, {"value"}_total=$total";
}

$argv = $_SERVER['argv'];
array_shift($argv);
if (count($argv) == 0) {
  echo "Usage: init | add key=value... | list | summary
";
  exit(2);
}
$cmd = array_shift($argv);
if ($cmd === "init") {
  if (!is_dir("data")) mkdir("data");
  file_put_contents($STORE_PATH, "");
  exit(0);
}
if ($cmd === "add") {
  append_record($STORE_PATH, parse_kv($argv, $FIELDS), $FIELDS);
  exit(0);
}
if ($cmd === "list") {
  foreach (load_records($STORE_PATH) as $r) {
    echo format_record($r, $FIELDS) . "
";
  }
  exit(0);
}
if ($cmd === "summary") {
  echo summary(load_records($STORE_PATH), $NUMERIC_FIELD) . "
";
  exit(0);
}
fwrite(STDERR, "Unknown command: $cmd
");
exit(2);
