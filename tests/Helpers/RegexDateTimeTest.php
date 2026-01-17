<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\RegexDateTime;
use PHPUnit\Framework\TestCase;

final class RegexDateTimeTest extends TestCase
{
    public function testIsDateTimeWithSecondsAndMeridiemValid()
    {
        $input = '2023-28-02 10:45:30 AM';
        $expected = [
            $input => [
                'year' => '2023',
                'year_separator' => '-',
                'day_28' => '28',
                'day_28_separator' => '-',
                'month_28' => '02',
                'date_time_separator' => ' ',
                'meridiem_hours_12' => '10',
                'meridiem_hours_separator' => ':',
                'meridiem_minutes' => '45',
                'meridiem_minutes_separator' => ':',
                'meridiem_seconds' => '30',
                'meridiem_separator' => ' ',
                'meridiem' => 'AM',
                'format' => 'ydm',
                'date_separator' => '-',
                'time_separator' => ':',
                'parsed' => [
                    'php_mask' => 'Y-d-m H:i:s A',
                    'iso_mask' => 'yyyy-dd-MM HH:mm:ss tt',
                    'warnings' => [],
                    'year' => 2023,
                    'month' => 2,
                    'day' => 28,
                    'hour' => 10,
                    'minute' => 45,
                    'second' => 30,
                    'fraction' => 0,
                    'errors' => [],
                    'is_localtime' => false
                ]
            ]
        ];
        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }

    public function testIsDateValid()
    {
        $input = '2024-29-02';
        $expected = [
            $input => [
                'leap_year' => '2024',
                'leap_year_separator' => '-',
                'leap_day' => '29',
                'leap_day_separator' => '-',
                'leap_month' => '02',
                'format' => 'ydm',
                'date_separator' => '-',
                'parsed' => [
                    'php_mask' => 'Y-d-m',
                    'iso_mask' => 'yyyy-dd-MM',
                    'warnings' => [],
                    'year' => 2024,
                    'month' => 2,
                    'day' => 29,
                    'hour' => false,
                    'minute' => false,
                    'second' => false,
                    'fraction' => false,
                    'errors' => [],
                    'is_localtime' => false
                ]
            ]
        ];
        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }

    public function testIsTimeValid()
    {
        $input = '13:45';
        $expected = [
            $input => [
                'timezone_hours_24' => '13',
                'timezone_hours_separator' => ':',
                'timezone_minutes' => '45',
                'format' => 'hms',
                'time_separator' => ':',
                'parsed' => [
                    'php_mask' => 'H:i',
                    'iso_mask' => 'HH:mm',
                    'warnings' => [],
                    'year' => false,
                    'month' => false,
                    'day' => false,
                    'hour' => 13,
                    'minute' => 45,
                    'second' => 0,
                    'fraction' => 0,
                    'errors' => [],
                    'is_localtime' => false
                ]
            ]
        ];
        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }

    public function testIsTimeWithMeridiemValid()
    {
        $input = '10:45 PM';
        $expected = [
            $input => [
                'meridiem_hours_12' => '10',
                'meridiem_hours_separator' => ':',
                'meridiem_minutes' => '45',
                'meridiem_separator' => ' ',
                'meridiem' => 'PM',
                'format' => 'hms',
                'time_separator' => ':',
                'parsed' => [
                    'php_mask' => 'H:i A',
                    'iso_mask' => 'HH:mm tt',
                    'warnings' => [],
                    'year' => false,
                    'month' => false,
                    'day' => false,
                    'hour' => 22,
                    'minute' => 45,
                    'second' => 0,
                    'fraction' => 0,
                    'errors' => [],
                    'is_localtime' => false
                ]
            ]
        ];
        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }

    public function testIsTimeWithTimespanValid()
    {
        $input = '13:45 +02:00';
        $expected = [
            $input => [
                'timezone_hours_24' => '13',
                'timezone_hours_separator' => ':',
                'timezone_minutes' => '45',
                'timezone_separator' => ' ',
                'timezone' => '+02:00',
                'format' => 'hms',
                'time_separator' => ':',
                'parsed' => [
                    'php_mask' => 'H:i P',
                    'iso_mask' => 'HH:mm zzz',
                    'warnings' => [],
                    'year' => false,
                    'month' => false,
                    'day' => false,
                    'hour' => 13,
                    'minute' => 45,
                    'second' => 0,
                    'fraction' => 0,
                    'errors' => [],
                    'is_localtime' => true,
                    'zone_type' => 1,
                    'zone' => 7200,
                    'is_dst' => false
                ]
            ]
        ];
        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }

    public function testIsTimeWithSecondsValid()
    {
        $input = '13:45:30';
        $expected = [
            $input => [
                'timezone_hours_24' => '13',
                'timezone_hours_separator' => ':',
                'timezone_minutes' => '45',
                'timezone_minutes_separator' => ':',
                'timezone_seconds' => '30',
                'format' => 'hms',
                'time_separator' => ':',
                'parsed' => [
                    'php_mask' => 'H:i:s',
                    'iso_mask' => 'HH:mm:ss',
                    'warnings' => [],
                    'year' => false,
                    'month' => false,
                    'day' => false,
                    'hour' => 13,
                    'minute' => 45,
                    'second' => 30,
                    'fraction' => 0,
                    'errors' => [],
                    'is_localtime' => false,
                ]
            ]
        ];
        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }

    public function testIsTimeWithSecondsAndMeridiemValid()
    {
        $input = '10:45:30 AM';
        $expected = [
            $input => [
                'meridiem_hours_12' => '10',
                'meridiem_hours_separator' => ':',
                'meridiem_minutes' => '45',
                'meridiem_minutes_separator' => ':',
                'meridiem_seconds' => '30',
                'meridiem_separator' => ' ',
                'meridiem' => 'AM',
                'format' => 'hms',
                'time_separator' => ':',
                'parsed' => [
                    'php_mask' => 'H:i:s A',
                    'iso_mask' => 'HH:mm:ss tt',
                    'warnings' => [],
                    'year' => false,
                    'month' => false,
                    'day' => false,
                    'hour' => 10,
                    'minute' => 45,
                    'second' => 30,
                    'fraction' => 0,
                    'errors' => [],
                    'is_localtime' => false,
                ]
            ]
        ];
        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }

    public function testIsTimeWithSecondsAndTimeSpanValid()
    {
        $input = '13:45:30 +02:00';
        $expected = [
            $input => [
                'timezone_hours_24' => '13',
                'timezone_hours_separator' => ':',
                'timezone_minutes' => '45',
                'timezone_minutes_separator' => ':',
                'timezone_seconds' => '30',
                'timezone_separator' => ' ',
                'timezone' => '+02:00',
                'format' => 'hms',
                'time_separator' => ':',
                'parsed' => [
                    'php_mask' => 'H:i:s P',
                    'iso_mask' => 'HH:mm:ss zzz',
                    'warnings' => [],
                    'year' => false,
                    'month' => false,
                    'day' => false,
                    'hour' => 13,
                    'minute' => 45,
                    'second' => 30,
                    'fraction' => 0,
                    'errors' => [],
                    'is_localtime' => true,
                    'zone_type' => 1,
                    'zone' => 7200,
                    'is_dst' => false,
                ]
            ]
        ];
        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }

    public function testIsDateTimeWithTimeSpanValid()
    {
        $input = '13:45:30 +02:00';
        $expected = [
            $input => [
                'timezone_hours_24' => '13',
                'timezone_hours_separator' => ':',
                'timezone_minutes' => '45',
                'timezone_minutes_separator' => ':',
                'timezone_seconds' => '30',
                'timezone_separator' => ' ',
                'timezone' => '+02:00',
                'format' => 'hms',
                'time_separator' => ':',
                'parsed' => [
                    'php_mask' => 'H:i:s P',
                    'iso_mask' => 'HH:mm:ss zzz',
                    'warnings' => [],
                    'year' => false,
                    'month' => false,
                    'day' => false,
                    'hour' => 13,
                    'minute' => 45,
                    'second' => 30,
                    'fraction' => 0,
                    'errors' => [],
                    'is_localtime' => true,
                    'zone_type' => 1,
                    'zone' => 7200,
                    'is_dst' => false,
                ]
            ]
        ];
        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }

    public function testIsDateTimeValid()
    {
        $input = '2023-28-02 10:45';
        $expected = [
            $input => [
                'year' => '2023',
                'year_separator' => '-',
                'day_28' => '28',
                'day_28_separator' => '-',
                'month_28' => '02',
                'date_time_separator' => ' ',
                'timezone_hours_24' => '10',
                'timezone_hours_separator' => ':',
                'timezone_minutes' => '45',
                'format' => 'ydm',
                'date_separator' => '-',
                'time_separator' => ':',
                'parsed' => [
                    'php_mask' => 'Y-d-m H:i',
                    'iso_mask' => 'yyyy-dd-MM HH:mm',
                    'warnings' => [],
                    'year' => 2023,
                    'month' => 2,
                    'day' => 28,
                    'hour' => 10,
                    'minute' => 45,
                    'second' => 0,
                    'fraction' => 0,
                    'errors' => [],
                    'is_localtime' => false
                ]
            ]
        ];
        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }

    public function testIsDateTimeWithMeridiemValid()
    {
        $input = '2023-28-02 10:45 PM';
        $expected = [
            $input => [
                'year' => '2023',
                'year_separator' => '-',
                'day_28' => '28',
                'day_28_separator' => '-',
                'month_28' => '02',
                'date_time_separator' => ' ',
                'meridiem_hours_12' => '10',
                'meridiem_hours_separator' => ':',
                'meridiem_minutes' => '45',
                'meridiem_separator' => ' ',
                'meridiem' => 'PM',
                'format' => 'ydm',
                'date_separator' => '-',
                'time_separator' => ':',
                'parsed' => [
                    'php_mask' => 'Y-d-m H:i A',
                    'iso_mask' => 'yyyy-dd-MM HH:mm tt',
                    'warnings' => [],
                    'year' => 2023,
                    'month' => 2,
                    'day' => 28,
                    'hour' => 22,
                    'minute' => 45,
                    'second' => 0,
                    'fraction' => 0,
                    'errors' => [],
                    'is_localtime' => false
                ]
            ]
        ];
        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }

    public function testIsDateTimeWithSecondsValid()
    {
        $input = '2023-28-02 10:45:30';
        $expected = [
            $input => [
                'year' => '2023',
                'year_separator' => '-',
                'day_28' => '28',
                'day_28_separator' => '-',
                'month_28' => '02',
                'date_time_separator' => ' ',
                'timezone_hours_24' => '10',
                'timezone_hours_separator' => ':',
                'timezone_minutes' => '45',
                'timezone_minutes_separator' => ':',
                'timezone_seconds' => '30',
                'format' => 'ydm',
                'date_separator' => '-',
                'time_separator' => ':',
                'parsed' => [
                    'php_mask' => 'Y-d-m H:i:s',
                    'iso_mask' => 'yyyy-dd-MM HH:mm:ss',
                    'warnings' => [],
                    'year' => 2023,
                    'month' => 2,
                    'day' => 28,
                    'hour' => 10,
                    'minute' => 45,
                    'second' => 30,
                    'fraction' => 0,
                    'errors' => [],
                    'is_localtime' => false
                ]
            ]
        ];

        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }

    public function testIsDateTimeAndTimespanValid()
    {
        $input = '2023-28-02 22:45 +02:00';
        $expected = [
            $input => [
                'year' => '2023',
                'year_separator' => '-',
                'day_28' => '28',
                'day_28_separator' => '-',
                'month_28' => '02',
                'date_time_separator' => ' ',
                'timezone_hours_24' => '22',
                'timezone_hours_separator' => ':',
                'timezone_minutes' => '45',
                'timezone_separator' => ' ',
                'timezone' => '+02:00',
                'format' => 'ydm',
                'date_separator' => '-',
                'time_separator' => ':',
                'parsed' => [
                    'php_mask' => 'Y-d-m H:i P',
                    'iso_mask' => 'yyyy-dd-MM HH:mm zzz',
                    'warnings' => [],
                    'year' => 2023,
                    'month' => 2,
                    'day' => 28,
                    'hour' => 22,
                    'minute' => 45,
                    'second' => 0,
                    'fraction' => 0,
                    'errors' => [],
                    'is_localtime' => true,
                    'zone_type' => 1,
                    'zone' => 7200,
                    'is_dst' => false
                ]
            ]
        ];
        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }

    public function testIsDateTimeWithSecondsAndTimespanValid()
    {
        $input = '2023-28-02 22:45:30 +02:00';
        $expected = [
            $input => [
                'year' => '2023',
                'year_separator' => '-',
                'day_28' => '28',
                'day_28_separator' => '-',
                'month_28' => '02',
                'date_time_separator' => ' ',
                'timezone_hours_24' => '22',
                'timezone_hours_separator' => ':',
                'timezone_minutes' => '45',
                'timezone_minutes_separator' => ':',
                'timezone_seconds' => '30',
                'timezone_separator' => ' ',
                'timezone' => '+02:00',
                'format' => 'ydm',
                'date_separator' => '-',
                'time_separator' => ':',
                'parsed' => [
                    'php_mask' => 'Y-d-m H:i:s P',
                    'iso_mask' => 'yyyy-dd-MM HH:mm:ss zzz',
                    'warnings' => [],
                    'year' => 2023,
                    'month' => 2,
                    'day' => 28,
                    'hour' => 22,
                    'minute' => 45,
                    'second' => 30,
                    'fraction' => 0,
                    'errors' => [],
                    'is_localtime' => true,
                    'zone_type' => 1,
                    'zone' => 7200,
                    'is_dst' => false,
                ]
            ]
        ];
        $result = RegexDateTime::getPattern($input);
        $this->assertEquals($expected, $result);
    }
}
