<?php

if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

require_once(TOOLKIT . '/fields/field.upload.php');

class FieldReflectedUpload extends FieldUpload {

    public function __construct() {
        parent::__construct();
        $this->_name = __('Reflected File Upload');
    }

    public function displaySettingsPanel(&$wrapper , $errors = null) {
        parent::displaySettingsPanel(&$wrapper , $errors);

        $label = Widget::Label('Name Expression
        <i>To access the other fields, use XPath: <code>{entry/field-one} static text {entry/field-two}</code></i>');
        $label->appendChild(Widget::Input(
                                'fields[' . $this->get('sortorder') . '][expression]' ,
                                $this->get('expression')
                            ));

        if (isset($errors['expression'])) $wrapper->appendChild(Widget::wrapFormElementWithError($label , $errors['expression']));
        else $wrapper->appendChild($label);

        $setting = new XMLElement('label', '<input name="fields[' . $this->get('sortorder') . '][unique]" value="1" type="checkbox"' . ($this->get('unique') == 0 ? '' : ' checked="checked"') . '/> ' . __('Always create unique name') . ' <i>' . __('This will append a unique token (uniqueupload behavior)') . '</i>');
        $wrapper->appendChild($setting);
    }

    public function checkFields(&$errors , $checkForDuplicates = true) {

        $expression = $this->get('expression');
        if (empty($expression)) {
            $errors['expression'] = __('This is a required field.');
        }
        parent::checkFields($errors , $checkForDuplicates);
    }

    public function commit() {
        if (!parent::commit()) return false;

        $id = $this->get('id');

        if ($id === false) return false;

        $fields = array();

        $fields['field_id'] = $id;
        $fields['destination'] = $this->get('destination');
        $fields['validator'] = ($fields['validator'] == 'custom' ? NULL : $this->get('validator'));
        $fields['expression'] = $this->get('expression');
        $fields['unique'] = $this->get('unique');
        $fields['unique'] = ($this->get('unique') ? 1 : 0);
        Symphony::Database()->query("DELETE FROM `tbl_fields_" . $this->handle() . "` WHERE `field_id` = '$id' LIMIT 1");
        return Symphony::Database()->insert($fields , 'tbl_fields_' . $this->handle());
    }

    private function getUniqueFilename($filename) {
        ## since uniqid() is 13 bytes, the unique filename will be limited to ($crop+1+13) characters;
        $crop = '30';
        return preg_replace("/([^\/]*)(\.[^\.]+)$/e" , "substr('$1', 0, $crop).'-'.uniqid().'$2'" , $filename);
    }

    public function checkPostFieldData($data , &$message , $entry_id = NULL) {
        extension_reflecteduploadfield::registerField($this);
        return self::__OK__;
    }

    public function processRawFieldData($data, &$status, &$message=null, $simulate = false, $entry_id = NULL) {
        if (is_array($data) and isset($data['name'])) $data['name'] = $this->getUniqueFilename($data['name']);
        return parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);
    }

    /**
     * @param  $entry
     * @return boolean
     * Renames the file based on the expression.
     * Inspired by Rowan Lewis <me@rowanlewis.com>
     */
    public function compile($entry) {
        $xpath = extension_reflecteduploadfield::getXPath($entry);

        $entry_id = $entry->get('id');
        $field_id = $this->get('id');
        $expression = $this->get('expression');
        $unique = $this->get('unique');
        $replacements = array();

        $old_value = $entry->getData($field_id);
        if(empty($old_value['file'])){
            return true;
        }
        preg_match("/([^\/]*)(\.[^\.]+)/e" , $old_value['file'] , $oldMatches);
        $old_filename = $oldMatches[1];
        $file_extension = $oldMatches[2];

        // Find queries:
        preg_match_all('/\{[^\}]+\}/' , $expression , $matches);

        // Find replacements:
        foreach ($matches[0] as $match) {
            $result = @$xpath->evaluate('string(' . trim($match , '{}') . ')');
            if (!is_null($result)) {
                $replacements[$match] = trim($result);
            }

            else {
                $replacements[$match] = '';
            }
        }

        // Apply replacements:
        $value = str_replace(
            array_keys($replacements) ,
            array_values($replacements) ,
            $expression
        );
        if($unique){
            $new_value = $value . '-' .  uniqid() . $file_extension;
        }else{
            $new_value = $value . $file_extension;
        }
        $new_value = Lang::createFilename($new_value);

        $abs_path = DOCROOT . '/' . trim($this->get('destination') , '/');
        $rel_path = str_replace('/workspace' , '' , $this->get('destination'));

        $old = $abs_path . '/' . $old_filename . $file_extension;
        $new = $abs_path . '/' . $new_value;
        if (rename($old , $new)) {
            $new_value = $rel_path . '/' . $new_value;
            // Save:
            $result = Symphony::Database()->update(
                array(
                     'file' => $new_value
                ) ,
                "tbl_entries_data_{$field_id}" ,
                "`entry_id` = '{$entry_id}'"
            );
            return true;
        } else {
            $message = __("Uploading '%s' failed. File upload stopped by extension." , array($this->get('label')));
            return false;
        }
    }
}
