<?php
class extension_reflecteduploadfield extends Extension {

    #########################
    ##### CLASS METHODS #####
    #########################

    /**
     * @static
     * @var array
     * Holds the fields that need post-save treatment
     */
    protected static $fields = array();

    /**
     * @static
     * @param $field
     * @return void
     * Add field to the $fields array for post save treatment
     */
    public static function registerField($field) {
        self::$fields[] = $field;
    }

    /**
     * @static
     * @param  $entry
     * @return DOMXPath
     * Gets XPATH Dom for entry.
     * Function by Rowan Lewis <me@rowanlewis.com>
     */
    public static function getXPath($entry) {
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

    ############################
    ##### INSTANCE METHODS #####
    ############################

    public function update($previousVersion) {
       if(version_compare($previousVersion, '1.0','<=')){
            Symphony::Database()->query("ALTER TABLE  `tbl_fields_reflectedupload` ADD  `unique` tinyint(1) default '0'");
        }
    }

    public function uninstall() {

        Symphony::Database()->query("DROP TABLE `tbl_fields_reflectedupload`");
    }

    public function install() {
        return Symphony::Database()->query(
            "CREATE TABLE `tbl_fields_reflectedupload` (
				 `id` int(11) unsigned NOT NULL auto_increment,
				 `field_id` int(11) unsigned NOT NULL,
				 `destination` varchar(255) NOT NULL,
				 `validator` varchar(50),
				 `expression` VARCHAR(255) DEFAULT NULL,
				 `unique` tinyint(1) default '0',
				  PRIMARY KEY (`id`),
				  KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
        );
    }

    /**
     * Delegation
     * @return array
     */
    public function getSubscribedDelegates() {
        return array(
            array(
                'page' => '/publish/new/' ,
                'delegate' => 'EntryPostCreate' ,
                'callback' => 'compileFields'
            ) ,

            array(
                'page' => '/publish/edit/' ,
                'delegate' => 'EntryPostEdit' ,
                'callback' => 'compileFields'
            ) ,
            array(
                'page' => '/frontend/' ,
                'delegate' => 'EventPostSaveFilter' ,
                'callback' => 'compileFields'
            )
        );
    }

    /**
     * @param $context
     * @return void
     * Delegate callback
     */
    public function compileFields($context) {
        foreach (self::$fields as $field) {
            if (!$field->compile($context['entry'])) {
                //TODO:Error
            }
        }
    }
}
