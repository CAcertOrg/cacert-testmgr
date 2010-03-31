<?php
defined('LIBARARY_PATH')
    || define('LIBRARY_PATH', realpath(dirname(__FILE__) . '/..'));

require_once('HumanReadableTime.php');

$hrf = HumanReadableTime::HR2Seconds($argv[1], true);
print 'Seconds: ' . $hrf . "\n";

print 'Default: ' . HumanReadableTime::Seconds2HR($hrf) . "\n";
print 'Week: ' . HumanReadableTime::Seconds2HR($hrf, 'w') . "\n";
print 'Day: ' . HumanReadableTime::Seconds2HR($hrf, 'd') . "\n";
print 'Hour: ' . HumanReadableTime::Seconds2HR($hrf, 'h') . "\n";
print 'Minute: ' . HumanReadableTime::Seconds2HR($hrf, 'm') . "\n";
print 'Second: ' . HumanReadableTime::Seconds2HR($hrf, 's') . "\n";

