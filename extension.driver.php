<?php

class extension_reflecteduploadfield extends Extension
{
    /*------------------------------------------------------------------------*/
    /* DEFINITION & SETTINGS
    /*------------------------------------------------------------------------*/

    /**
     * Name of the extension field table
     * @var string
     *
     * @since version 1.3.0
     */

    const FIELD_TBL_NAME = 'tbl_fields_reflectedupload';

    /**
     * Holds the fields that need post-save treatment
     * @static
     * @var array
     *
     * @since version 1.0.0
     */

    protected static $fields = array();

    /**
     * Add field to the $fields array for post save treatment
     * @static
     * @param $field
     * @return void
     *
     * @since version 1.0.0
     */

    public static function registerField($field)
    {
        self::$fields[] = $field;
    }

    /**
     * GET SUBSCRIBES DELEGATES
     *
     * http://www.getsymphony.com/learn/api/2.4/toolkit/extension/#getSubscribedDelegates
     *
     * @since version 1.0.0
     */

    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page' => '/publish/new/' ,
                'delegate' => 'EntryPostCreate' ,
                'callback' => 'compileFields'
            ),
            array(
                'page' => '/publish/edit/' ,
                'delegate' => 'EntryPostEdit' ,
                'callback' => 'compileFields'
            ),
            array(
                'page' => '/frontend/' ,
                'delegate' => 'EventPostSaveFilter' ,
                'callback' => 'compileFields'
            )
        );
    }

    /**
     * COMPILE FIELDS (delegate callback)
     *
     * @param $context
     * @return void
     * @since version 1.0.0
     */

    public function compileFields($context)
    {
        foreach (self::$fields as $field) {
            if (!$field->compile($context['entry'])) {
                // TODO:ERROR
            }
        }
    }

    /*------------------------------------------------------------------------*/
    /* INSTALL / UPDATE / UNINSTALL
    /*------------------------------------------------------------------------*/

    /**
     * INSTALL
     *
     * http://www.getsymphony.com/learn/api/2.4/toolkit/extension/#install
     *
     * @since version 1.0.0
     */

    public function install()
    {
        return self::createFieldTable();
    }

    /**
     * CREATE FIELD TABLE
     *
     * @since version 1.3.0
     */

    public static function createFieldTable()
    {
        $tbl = self::FIELD_TBL_NAME;

        return Symphony::Database()->query("
            CREATE TABLE IF NOT EXISTS `$tbl` (
                `id` int(11) unsigned NOT NULL auto_increment,
                `field_id` int(11) unsigned NOT NULL,
                `destination` varchar(255) NOT NULL,
                `validator` varchar(50),
                `expression` VARCHAR(255) DEFAULT NULL,
                `unique` tinyint(1) default '0',
                 PRIMARY KEY (`id`),
                 KEY `field_id` (`field_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }

    /**
     * UPDATE
     *
	 * http://www.getsymphony.com/learn/api/2.4/toolkit/extension/#update
	 *
     * @since version 1.0.0
     */

    public function update($previousVersion = false)
    {
        // updating from versions prior to 1.0
        if(version_compare($previousVersion, '1.0','<=')) {
            Symphony::Database()->query("ALTER TABLE  `tbl_fields_reflectedupload` ADD  `unique` tinyint(1) default '0'");
        }

        // updating from versions prior to 1.2
        if (version_compare($previous_version, '1.2', '<')) {

            // Remove directory from the upload fields, fixes Symphony Issue #1719
            $upload_tables = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_reflectedupload`");

            if(is_array($upload_tables) && !empty($upload_tables)) foreach($upload_tables as $field) {
                Symphony::Database()->query(sprintf(
                    "UPDATE tbl_entries_data_%d SET file = substring_index(file, '/', -1)",
                    $field
                ));
             }
        }
    }

    /**
     * UNINSTALL
     *
	 * http://www.getsymphony.com/learn/api/2.4/toolkit/extension/#uninstall
	 *
     * @since version 1.0.0
     */

    public function uninstall()
    {
        return self::deleteFieldTable();
    }

    /**
     * DELETE FIELD TABLE
     *
     * @since version 1.3.0
     */

    public static function deleteFieldTable()
    {
        $tbl = self::FIELD_TBL_NAME;

        return Symphony::Database()->query("
            DROP TABLE IF EXISTS `$tbl`
        ");
    }

}
