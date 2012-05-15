<?php


require_once(TOOLKIT . '/class.xsltprocess.php');

Class fieldUnique_link extends Field
{
    public function __construct(&$parent)
    {

        parent::__construct($parent);
        $this->_name = __('Unique Link');
        $this->_required = false;

        $this->set('required', 'no');


    }

    public function allowDatasourceParamOutput()
    {
        return true;
    }

    public function displayPublishPanel(&$wrapper, $data = NULL, $flagWithError = NULL, $fieldnamePrefix = NULL, $fieldnamePostfix = NULL)
    {
        $value = General::sanitize($data['code']);
        $label = Widget::Label($this->get('label'));

        $label->appendChild(new XMLElement('a', __('Select Link'), array('style' => 'float: right;', 'onclick' => "
                document.getElementById('unique_link_" . $this->get('id') . "').select(); return false;
            ")));

        $link = str_replace('[URL]', URL, $this->get('link'));
        $link = str_replace('[CODE]', $data['code'], $link);

        $value = $data === null ? __('A link will automaticly be generated when you save this entry') : $link;

        $label->appendChild(Widget::Input('fields' . $fieldnamePrefix . '[' . $this->get('element_name') . ']' . $fieldnamePostfix,
                                          $value,
                                          null, array('id' => 'unique_link_' . $this->get('id'), 'readonly' => 'readonly', 'style' => 'background: #eee; color: #666; border: 1px solid #ccc;')));

        if ($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
        else $wrapper->appendChild($label);
    }

    public function isSortable()
    {
        return true;
    }

    public function canFilter()
    {
        return true;
    }

    public function canImport()
    {
        return true;
    }

    public function buildSortingSQL(&$joins, &$where, &$sort, $order = 'ASC')
    {
        $joins .= "LEFT OUTER JOIN `tbl_entries_data_" . $this->get('id') . "` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
        $sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand')) ? 'RAND()'
                : "`ed`.`value` $order");
    }

    public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false)
    {
        $field_id = $this->get('id');

        if (self::isFilterRegex($data[0])) {
            $this->_key++;

            if (preg_match('/^regexp:/i', $data[0])) {
                $pattern = preg_replace('/regexp:/i', null, $this->cleanValue($data[0]));
                $regex = 'REGEXP';
            } else {
                $pattern = preg_replace('/not-?regexp:/i', null, $this->cleanValue($data[0]));
                $regex = 'NOT REGEXP';
            }

            $joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
            $where .= "
					AND (
						t{$field_id}_{$this->_key}.value {$regex} '{$pattern}'
						OR t{$field_id}_{$this->_key}.handle {$regex} '{$pattern}'
					)
				";

        } elseif ($andOperation) {
            foreach ($data as $value) {
                $this->_key++;
                $value = $this->cleanValue($value);
                $joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
                $where .= "
						AND (
							t{$field_id}_{$this->_key}.value = '{$value}'
							OR t{$field_id}_{$this->_key}.handle = '{$value}'
						)
					";
            }

        } else {
            if (!is_array($data)) $data = array($data);

            foreach ($data as &$value) {
                $value = $this->cleanValue($value);
            }

            $this->_key++;
            $data = implode("', '", $data);
            $joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
            $where .= "
					AND (
						t{$field_id}_{$this->_key}.value IN ('{$data}')
						OR t{$field_id}_{$this->_key}.handle IN ('{$data}')
					)
				";
        }

        return true;
    }

    public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null)
    {

        $status = self::__OK__;

        if (strlen(trim($data)) == 0) return array();

        $callback = Administration::instance()->getPageCallback();

        if ($callback['context']['page'] == 'new') {
            // new entry
            // Generate some random code:
            $code = sha1(time() + (rand(0, 999) / 1000));
            $data = $code;
            $timestamp = time();
        } else {
            $row = Symphony::Database()->fetchRow(0, 'SELECT * FROM `tbl_entries_data_' . $this->get('id') . '` WHERE `entry_id` = ' . $entry_id . ';');
            $timestamp = $row['timestamp'];
            $data = $row['code'];
        }

        $result = array(
            'code' => $data,
            'timestamp' => $timestamp
        );

        return $result;

    }

    public function canPrePopulate()
    {
        return true;
    }

    /**
     * Delete an entry according to the code that's being used
     * @param  $code
     * @return void
     */
    private function deleteEntryAccordingToCode($code)
    {
        $entry_id = Symphony::Database()->fetchVar('entry_id', 0, 'SELECT `entry_id` FROM `tbl_entries_data_' . $this->get('id') . '` WHERE `code` = \'' . $code . '\';');
        $em = new EntryManager($this);
        $em->delete($entry_id);
        // Redirect:
        redirect(URL . $_SERVER['REQUEST_URI']);
    }


    public function appendFormattedElement(&$wrapper, $data, $encode = false)
    {

        $value = $data['code'];
        $seconds_passed = time() - $data['timestamp'];
        $hours_passed = $seconds_passed / 3600;
        $hours_left = round($this->get('hours') - $hours_passed);

        $valid = $this->get('hours') - $hours_passed > 0 ? 'yes' : 'no';

        if ($valid == 'no' && $this->get('auto_delete') == 1) {
            // Delete the entry and redirect:
            $this->deleteEntryAccordingToCode($value);
        }

        $link = str_replace('[URL]', URL, $this->get('link'));
        $link = str_replace('[CODE]', $value, $link);

        $wrapper->appendChild(
            new XMLElement(
                $this->get('element_name'), $link, array('code' => $value, 'hours-left' => $hours_left, 'valid' => $valid)
            )
        );
    }

    public function commit()
    {

        if (!parent::commit()) return false;

        $id = $this->get('id');

        if ($id === false) return false;

        $fields = array();

        $fields['field_id'] = $id;
        $fields['link'] = $this->get('link');
        $fields['hours'] = $this->get('hours');
        $fields['auto_delete'] = $this->get('auto_delete') == 'yes' ? 1 : 0;

        print_r($fields);

        Symphony::Database()->query("DELETE FROM `tbl_fields_" . $this->handle() . "` WHERE `field_id` = '$id' LIMIT 1");

        return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());

    }

    public function prepareTableValue($data, XMLElement $link = null)
    {

        $seconds_passed = time() - $data['timestamp'];
        $hours_passed = $seconds_passed / 3600;
        $hours_left = round($this->get('hours') - $hours_passed);
        $valid = $this->get('hours') - $hours_passed > 0 ? 'yes' : 'no';

        if ($valid == 'no' && $this->get('auto_delete') == 1) {
            // Delete the entry and redirect:
            $this->deleteEntryAccordingToCode($data['code']);
        }

        if ($valid == 'yes') {
            $value = __('Link valid for') . ' ' . $hours_left . ' ' . __('hours');
        } else {
            $value = __('Link no longer valid');
        }
        if (strlen($value) == 0) $value = __('None');

        if ($link) {
            $link->setValue($value);

            return $link->generate();
        }

        return $value;
    }

    public function setFromPOST($postdata)
    {
        parent::setFromPOST($postdata);
        if ($this->get('validator') == '') $this->remove('validator');
    }

    public function displaySettingsPanel(&$wrapper, $errors = null)
    {
        parent::displaySettingsPanel($wrapper, $errors);

        $link = $this->get('link');
        $hours = $this->get('hours');
        $link = !empty($link) ? $link : '[URL]/[CODE]/';
        $hours = !empty($hours) ? $hours : 24;

        $div = new XMLElement('div', NULL, array('class' => 'group'));
        $div->appendChild(Widget::Label(__('Link (parameters: <em>[URL]</em>, <em>[CODE]</em>):'),
                                        Widget::Input('fields[' . $this->get('sortorder') . '][link]', $link)));
        $div->appendChild(Widget::Label(__('Hours valid:'),
                                        Widget::Input('fields[' . $this->get('sortorder') . '][hours]', $hours)));
        $wrapper->appendChild($div);

        $div = new XMLElement('div', NULL, array('class' => 'compact'));

        $label = Widget::Label();
        $label->setAttribute('class', 'meta');
        $input = Widget::Input('fields[' . $this->get('sortorder') . '][auto_delete]', 'yes', 'checkbox');
        if ($this->get('auto_delete') == 1) $input->setAttribute('checked', 'checked');

        $label->setValue($input->generate() . ' ' . __('Delete the entry when link is no longer valid'));
        $div->appendChild($label);

        $this->appendShowColumnCheckbox($div);
        $wrapper->appendChild($div);

    }

    public function createTable()
    {

        return Symphony::Database()->query(

            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `code` varchar(255) default NULL,
				  `timestamp` varchar(255) NOT NULL,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `entry_id` (`entry_id`)
				) ENGINE=MyISAM;"

        );
    }


}

