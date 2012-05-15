<?php

Class extension_field_unique_link extends Extension
{
    /**
     * Installation script
     * @return void
     */
    public function install()
    {
        Symphony::Database()->query("CREATE TABLE IF NOT EXISTS `tbl_fields_unique_link` (
            `id` int(11) unsigned NOT NULL auto_increment,
            `field_id` int(11) unsigned NOT NULL,
            `link` VARCHAR(255) DEFAULT NULL,
            `hours` INT NOT NULL,
            `auto_delete` INT(1) NOT NULL,
            PRIMARY KEY  (`id`),
            KEY `field_id` (`field_id`)
        )");
    }

    /**
     * Uninstallation script
     * @return void
     */
    public function uninstall()
    {
        Symphony::Database()->query('DROP TABLE `tbl_fields_unique_link`');
    }
}
