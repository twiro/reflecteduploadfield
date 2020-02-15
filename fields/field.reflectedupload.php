<?php

if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

require_once(TOOLKIT . '/fields/field.upload.php');

class FieldReflectedUpload extends FieldUpload
{
    /*------------------------------------------------------------------------*/
    /* DEFINITION
    /*------------------------------------------------------------------------*/

    /**
     * CONSTRUCT
     *
     * Constructor for the Field object
     *
     * http://www.getsymphony.com/learn/api/2.4/toolkit/fieldupload/#__construct
     *
     * @since version 1.0.0
     */

    public function __construct()
    {
        // call the parent constructor
        parent::__construct();

        // set the name of the field
        $this->_name = __('Reflected File Upload');
    }

    /*------------------------------------------------------------------------*/
    /* SETTINGS / SECTION EDITOR
    /*------------------------------------------------------------------------*/

    /**
     * DISPLAY SETTINGS PANEL
     *
     * http://www.getsymphony.com/learn/api/2.4/toolkit/fieldupload/#displaySettingsPanel
     *
     * @since version 1.0.0
     */

    public function displaySettingsPanel(XMLElement &$wrapper , $errors = null)
    {
        parent::displaySettingsPanel($wrapper , $errors);

        $label = Widget::Label('Expression');
        $label->appendChild(Widget::Input(
            'fields[' . $this->get('sortorder') . '][expression]' ,
            $this->get('expression')
        ));

        $help = new XMLElement('p');
        $help->setAttribute('class', 'help');
        $help->setValue(__('Use XPath to access other fields: <code>{//entry/field-one} static text {//entry/field-two}</code>.'));
        $label->appendChild($help);

        if (isset($errors['expression'])) {
            $wrapper->appendChild(Widget::wrapFormElementWithError($label , $errors['expression']));
        } else {
            $wrapper->appendChild($label);
        }

        $setting = new XMLElement('label', '<input name="fields[' . $this->get('sortorder') . '][unique]" value="1" type="checkbox"' . ($this->get('unique') == 0 ? '' : ' checked="checked"') . '/> ' . __('Create unique filenames') . ' <i>' . __('This will append a random token to the filename to guarantee uniqueness') . '</i>');
        $wrapper->appendChild($setting);
    }

    /**
     * CHECK FIELDS
     *
     * Check the field's settings to ensure they are valid on the section editor
     *
     * http://www.getsymphony.com/learn/api/2.4/toolkit/fieldupload/#checkFields
     *
     * @since version 1.0.0
     */

    public function checkFields(array &$errors , $checkForDuplicates = true) {

        $expression = $this->get('expression');
        if (empty($expression)) {
            $errors['expression'] = __('This is a required field.');
        }
        parent::checkFields($errors , $checkForDuplicates);
    }

    /**
     * COMMIT
     *
     * Commit the settings of this field from the section editor to create an
     * instance of this field in a section.
     *
     * http://www.getsymphony.com/learn/api/2.4/toolkit/fieldupload/#commit
     *
     * @since version 1.0.0
     */

    public function commit()
    {
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

    /*------------------------------------------------------------------------*/
    /* PROCESSS & SAVE DATA
    /*------------------------------------------------------------------------*/

    /**
     * CHECK POST FIELD DATA
     *
     * Check the field data that has been posted from a form. This will set the
     * input message to the error message or to null if there is none.
     * Any existing message value will be overwritten.
     *
     * http://www.getsymphony.com/learn/api/2.4/toolkit/fieldupload/#checkPostFieldData
     *
     * @since version 1.0.0
     */

    public function checkPostFieldData($data , &$message , $entry_id = NULL)
    {
        extension_reflecteduploadfield::registerField($this);
        return self::__OK__;
    }

    /**
     * PROCESS RAW FIELD DATA
     *
     * http://www.getsymphony.com/learn/api/2.4/toolkit/fieldupload/#processRawFieldData
     *
     * @since version 1.0.0
     */

    public function processRawFieldData($data, &$status, &$message=null, $simulate = false, $entry_id = NULL)
    {
        if (is_array($data) and isset($data['name'])) {
            $data['name'] = $this->getUniqueFilename($data['name']);
        }

        return parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);
    }

    /**
     * GET UNIQUE FILENAME
     *
     * @since version 1.0.0
     */

    private static function getUniqueFilename($filename)
    {
        return preg_replace_callback(
            '/([^\/]*)(\.[^\.]+)$/',
            function ($m) {
                // uniqid() is 13 bytes, so the unique filename will be limited to ($crop + 1 + 13) characters
                $crop = '60';
                return substr($m[1], 0, $crop) . '-' . uniqid() . $m[2];
            },
            $filename
        );
    }

    /*------------------------------------------------------------------------*/
    /* DATA SOURCE OUTPUT
    /*------------------------------------------------------------------------*/

    /**
     * APPEND FORMATTED ELEMENT
     *
     * http://www.getsymphony.com/learn/api/2.4/toolkit/fieldupload/#appendFormattedElement
     *
     * @since version 1.3.0
     */

    public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
    {
        parent::appendFormattedElement($wrapper, $data);
        $field = $wrapper->getChildrenByName($this->get('element_name'));
        if(!empty($field)) {
            end($field)->appendChild(new XMLElement('clean-filename', General::sanitize(self::getCleanFilename(basename($data['file'])))));
        }
    }

    /**
     * GET CLEAN FILENAME
     *
     * @since version 1.3.0
     */

    private static function getCleanFilename($filename)
    {
        return preg_replace("/([^\/]*)(\-[a-f0-9]{13})(\.[^\.]+)$/", '$1$3', $filename);
    }

    /*------------------------------------------------------------------------*/
    /* XPATH & REFLECTION
    /*------------------------------------------------------------------------*/

    /**
     * COMPILE
     *
     * @param  $entry
     * @return boolean
     *
     * Renames the file based on the expression.
     * Inspired by Rowan Lewis <me@rowanlewis.com>
     *
     * @since version 1.0.0
     */

    public function compile($entry)
    {
        $xpath = $this->getXPath($entry);

        $entry_id = $entry->get('id');
        $field_id = $this->get('id');
        $expression = $this->get('expression');
        $unique = $this->get('unique');
        $replacements = array();

        $old_value = $entry->getData($field_id);
        if(empty($old_value['file'])){
            return true;
        }
        preg_match("/([^\/]*)(\.[^\.]+)/" , $old_value['file'] , $oldMatches);
        $old_filename = $oldMatches[1];
        $file_extension = $oldMatches[2];

        // Find queries:
        preg_match_all('/\{[^\}]+\}/' , $expression , $matches);

        // Find replacements:
        foreach ($matches[0] as $match) {
            $result = @$xpath->evaluate('string(' . trim($match , '{}') . ')');
            if (!is_null($result)) {
                $replacements[$match] = trim($result);
            } else {
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

    /**
     * GET XPATH
     *
     * @static
     * @param  $entry
     * @return DOMXPath
     *
     * Function by Rowan Lewis <me@rowanlewis.com>
     *
     * @since version 1.0.0
     */

    public static function getXPath($entry)
    {
        $entry_xml = new XMLElement('entry');
        $section_id = $entry->get('section_id');
        $data = $entry->getData();
        $fields = array();
        $entry_xml->setAttribute('id' , $entry->get('id'));

        $associated = $entry->fetchAllAssociatedEntryCounts();

        if (is_array($associated) and !empty($associated)) {
            foreach ($associated as $section => $count) {
                $handle = Symphony::Database()->fetchVar('handle' , 0 , "
                    SELECT s.handle FROM `tbl_sections` AS s
                    WHERE s.id = '{$section}'
                    LIMIT 1
                    ");
                $entry_xml->setAttribute($handle , (string)$count);
            }
        }

        // Add fields:
        foreach ($data as $field_id => $values) {
            if (empty($field_id)) continue;
            $fm = new FieldManager($entry);
            $field =& $fm->fetch($field_id);
            $field->appendFormattedElement($entry_xml , $values , false , null);
        }

        $xml = new XMLElement('data');
        $xml->appendChild($entry_xml);
        $dom = new DOMDocument();
        $dom->strictErrorChecking = false;
        $dom->loadXML($xml->generate(true));

        $xpath = new DOMXPath($dom);

        if (version_compare(phpversion() , '5.3' , '>=')) {
            $xpath->registerPhpFunctions();
        }

        return $xpath;
    }

}
