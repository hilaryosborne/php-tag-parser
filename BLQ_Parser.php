<?php

/**

Copyright (c) 2015 BlockQuote pty ldt
http://www.theblockquote.com

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

Author Hilary Osborne

*/

/**
    USAGE EXAMPLES
    
    $text = '
        [[people]]
            [[person name="Barry"]]
            [[person name="ryan"]]
            [[person name="Kerry"]]
                This is some text
                [[job role="boss"]]
            [[\person]]
        [[\people]]
    ';
    
    // Create a new parser
    $blq_parser = new BLQ_Parser($text);
    // Retrieve a list of shortcodes
    $_objs = $blq_parser
        // Change these to whatever character(s) used for begining and ending a tag
        ->Set_Ends('[[',']]')
        // Parses the string
        ->Parse()
        // THEN CHOOSE ONE OF THE FOLLOWING
        // Gets all opening, closing and nomad objects
        ->Get_Elements();
        // Gets all opening and nomad objects
        ->Get_Flattened();
        // Gets all opening and nomad objects in a hierachy array
        ->Get_Hierachy();
        
*/

class BLQ_Parser {
    
    public $string;
    
    public $string_exploded;
    
    public $_elements;
    
    public $tag_open;
    
    public $tag_open_first;
    
    public $tag_close;
    
    public $tag_close_first;
    
    public function __construct($text) {
        // Store the raw string
        $this->string = $text;
        // Split the string into an array
        $this->string_exploded = str_split($text);
        // Determine the first open character
        $this->tag_open_first = substr($this->tag_open,0,1);
        // Determine the first close character
        $this->tag_close_first = substr($this->tag_close,0,1);
    }
    
    public function Set_Ends($opening,$closing) {
        // Determine the first open character
        $this->tag_open = $opening;
        // Determine the first close character
        $this->tag_close = $closing;
        // Determine the first open character
        $this->tag_open_first = substr($this->tag_open,0,1);
        // Determine the first close character
        $this->tag_close_first = substr($this->tag_close,0,1);
        // Return for chaining
        return $this;
    }
    
    public function Parse() {
        // Build the elements
        $this->_Build_Elements();
        // Build the attributes
        $this->_Build_Attributes();
        // Build the hierachy
        $this->_Build_Hierarchy();
        // Return for chaining
        return $this;
    }
    
    public function Get_Elements() {
        // Return then elements
        return $this->_elements;
    }
    
    public function Get_Flattened() {
        // Retrieve the elements
        $elements = $this->_elements;
        // The flat array
        $_flattened = array();
        // Loop through each element
        foreach ($elements as $k => $el) {
            // Dont' include any closing tags
            if ($el->is_closing_tag) { continue; }
            // Add the element
            $_flattened[] = $el;
        }
        // Return the flattened array
        return $_flattened;
    }
    
    public function Get_Hierachy() {
        // Return the hierachy
        return $this->_hierarchy;
    }
    
    protected function _Is_Opening_Char($k,$_array_iter) {
        // Check if the character matches the opening character of the closing tag
        if ($_array_iter[$k] != $this->tag_open_first) { return false; }
        // Check if the preceeding character is an escape
        if ($k != 0 && $_array_iter[($k-1)] == '\\') { return false; }
        // Split the string into an array
        $char_list = str_split($this->tag_open);
        // Is tag open flag
        $is_opening = true;
        // Loop through each char list
        foreach ($char_list as $_k => $char) {
            // New array index
            $i = $k+$_k;
            // Determine if the tag is continuing the opening bracked trend
            if (!isset($_array_iter[$i]) || $char != $_array_iter[$i]) { $is_opening = false; }
        }
        // If the array is the opening
        if ($is_opening) { 
            // Advance the array pointer
            for ($i=1;$i<count($char_list);$i++) { $_array_iter->next(); }
            // Return out
            return true;
        }
    }
    
    protected function _Is_Closing_Char($k,$_array_iter) {
        // Check if the character matches the opening character of the closing tag
        if ($_array_iter[$k] != $this->tag_close_first) { return false; }
        // Check if the preceeding character is an escape
        if ($k != 0 && $_array_iter[($k-1)] == '\\') { return false; }
        // Split the string into an array
        $char_list = str_split($this->tag_close);
        // Is tag open flag
        $is_closing = true;
        // Loop through each char list
        foreach ($char_list as $_k => $char) {
            // New array index
            $i = $k+$_k;
            // Determine if the tag is continuing the opening bracked trend
            if (!isset($_array_iter[$i]) || $char != $_array_iter[$i]) { $is_closing = false; }
        }
        // If the array is the opening
        if ($is_closing) { 
            // Advance the array pointer
            for ($i=1;$i<count($char_list);$i++) { echo $_array_iter->next(); }
            // Return out
            return true;
        }
    }
    
    protected function _Is_Closing_Tag($_array_iter) {
        
        $current_key = $_array_iter->key();
        
        if (!isset($_array_iter[$current_key+1])) { return false; }
        
        return $_array_iter[$current_key+1] == '/' ? true : false;
    }
    
    protected function _Build_Elements() {
        // Split the string into an array
        $_array = new ArrayObject(str_split($this->string));
        
        $_array_iter = $_array->getIterator(); $current_el = false;
        // Loop through each of the characters
        foreach ($_array_iter as $k => $char) {
            // If this is the start of a new tag
            if ($this->_Is_Opening_Char($k,$_array_iter)) { 
                // If there is previous tag contents, add a new simple items
                $current_el = new BLQ_Element();
                // Add to the elements array
                $this->_elements[] = $current_el;
                // Add the character
                $current_el->Is_Tag(true);
                $current_el->Is_Opening_Tag($this->_Is_Closing_Tag($_array_iter) ? false : true);
                $current_el->Is_Closing_Tag($this->_Is_Closing_Tag($_array_iter) ? true : false);
            } // Otherwise if this is the end of a tag
            elseif ($this->_Is_Closing_Char($k,$_array_iter)) {
                // If this was a closing tag
                if ($current_el->is_closing_tag) {
                    // Search for opening tags
                    $this-> _Link_Elements($current_el);
                }
                // Set the current element to false
                $current_el = false;
            }  // Otherwise simply build the content 
            else { 
                // If there is no current element
                if (!$current_el) {
                    // If there is previous tag contents, add a new simple items
                    $current_el = new BLQ_Element();
                    // Add to the elements array
                    $this->_elements[] = $current_el;
                }
                // Add the character to the element
                $current_el->Add_Char($char);     
            }
        }
    }
    
    protected function _Link_Elements($el) {
        // Retrieve the array of elements
        $_elements = $this->_elements;
        // Loop through each of the elements
        foreach ($_elements as $k => $_el) {
            // If this is not a tag, move on
            if (!$_el->is_tag) { continue; }
            // If this is not an opening tag, move on
            if (!$_el->is_opening_tag) { continue; }
            // If the tag text does not match, move on
            if ($_el->tag != $el->tag) { continue; }
            // If the element is already linked
            if ($_el->linked_with) { continue; }
            // Set the linked with on the opening tag
            $_el->linked_with = $el;
            // Set the linked with on the closing tag
            $el->linked_with = $_el;
            // Return out
            return true;
        }
    }
    
    protected function _Build_Attributes() {
        // Retrieve the array of elements
        $_elements = $this->_elements;
        // Loop through each of the elements
        foreach ($_elements as $k => $_el) {
            // If this is not a tag, move on
            if (!$_el->is_tag) { continue; }
            // If this is not an opening tag, move on
            if (!$_el->is_opening_tag) { continue; }
            // Look for all attribute matches
            preg_match_all('/(\w+)\s*=\s*"(.*?)"/', $_el->string, $matches);
            // The attributes list
            $attributes = array();
            // Loop through each attribute match
            foreach ($matches[1] as $_k => $_attr) {
                // Populate the attributes list
                $attributes[$_attr] = $matches[2][$_k];
            } 
            // Set the linked with on the opening tag
            $_el->attributes = $attributes;
        }
    }
    
    protected function _Build_Hierarchy() {
        // Retrieve the array of elements
        $_elements = $this->_elements;
        
        $parent_el = false; $level = 0;
        // Loop through each of the elements
        foreach ($_elements as $k => $_el) {
            // Set the element's level
            $_el->level = $level;
            // If this is not a tag
            if (!$_el->is_tag) {
                // If there is currently no hierachy
                if (!$parent_el) { $this->_hierarchy[] = $_el; continue; }
                // Add this as a child
                $parent_el->Add_Child($_el); continue;
            }
            // If there is currently no hierachy
            if (!$parent_el && $_el->is_opening_tag) { 
                // Add to the hierachy array
                $this->_hierarchy[] = $_el; 
                // Set as the parent_el
                if ($_el->linked_with) { $parent_el = $_el; $level++; }
                
                continue; 
            }
            
            if ($_el->is_opening_tag) {
                // Add the child element
                $parent_el->Add_Child($_el);
                // If this element is linked with another
                if ($_el->linked_with) {
                    // Set the new parent element
                    $parent_el = $_el; $level++;
                }
            }
            
            if ($_el->is_closing_tag && $_el->linked_with) {
                // Update the parent element
                $parent_el = $_el->linked_with->parent_el; $level--;
            }
        }
    }
}

class BLQ_Element {
    
    public $tag;
    
    public $string;
    
    public $children;
    
    public $parent_el;
    
    public $linked_with;
    
    public $is_tag;
    
    public $attributes;
    
    public $is_tag_complete;
    
    public $is_opening_tag;
    
    public $is_closing_tag;
    
    public $level;
    
    public function Add_Char($var) {
        
        $this->string .= $var;
        
        if (!$this->is_tag || $this->is_tag_complete) { return $this; }
            
        if ($var == ' ') { 

            $this->is_tag_complete = true; 
        }  
        elseif ($var != '/') { $this->tag .= $var; }
        
        return $this;   
    }
    
    public function Add_Child($child_el) {
        // Add the child element
        $this->children[] = $child_el;
        
        $child_el->parent_el = $this;
        
        return $this;
    }
    
    public function Is_Tag($var) {
        
        $this->is_tag = $var;
        
        return $this;
    }
    
    public function Is_Opening_Tag($var) {
        
        $this->is_opening_tag = $var;
        
        return $this;
    }
    
    public function Is_Closing_Tag($var) {
        
        $this->is_closing_tag = $var;
        
        return $this;
    }
    
}