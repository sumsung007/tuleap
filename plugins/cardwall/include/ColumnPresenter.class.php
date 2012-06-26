<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
class ColumnPresenter {
    
    private $swimline_id;
    private $swimline_field_values;
    private $card_field_id;
    
    public function __construct($swimline_id, array $swimline_field_values, $card_field_id) {
        $this->swimline_id           = $swimline_id;
        $this->swimline_field_values = $swimline_field_values;
        $this->card_field_id         = $card_field_id;
    }

    public function getDropIntoClass() {
        $classes = array();
        foreach ($this->swimline_field_values as $id) {
            $classes[] = 'drop-into-'.$this->swimline_id.'-'.$id;
        }
        return implode(' ', $classes);
    }

    public function getCardFieldId() {
        return $this->card_field_id;
        
    }
    //put your code here
}

?>
