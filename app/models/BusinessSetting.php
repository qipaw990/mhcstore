<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class BusinessSetting extends Model
{
    protected string $table = 'business_settings';
    protected array $fillable = ['key_name', 'value_text'];

    public static function get(string $key, $default = null)
    {
        $res = Database::fetchOne("SELECT `value_text` FROM `business_settings` WHERE `key_name` = ? LIMIT 1", [$key]);
        return $res ? $res['value_text'] : $default;
    }

    public static function set(string $key, $value): bool
    {
        $exists = Database::fetchOne("SELECT id FROM `business_settings` WHERE `key_name` = ? LIMIT 1", [$key]);
        if ($exists) {
            return Database::update('business_settings', ['value_text' => $value], '`key_name` = ?', [$key]);
        }
        Database::insert('business_settings', ['key_name' => $key, 'value_text' => $value]);
        return true;
    }
}
