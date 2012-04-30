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


class Planning_ShowPresenter {
    
    public $__ = array(__CLASS__, '__trans');
    
    public $planning_id;
    public $planning_name;
    public $group_id;
    public $destination_id;
    public $destination_title;
    public $destination_link;
    public $destination_xref;
    
    private $artifacts_to_select;
    private $artifact;
    private $content_view;
    private $current_user;
    private $current_uri;
    
    /**
     * @var Planning
     */
    private $planning;
    
    /**
     * @var Tracker
     */
    private $planning_tracker;
    
    public function __construct(Planning $planning,
                                Tracker_CrossSearch_SearchContentView $content_view,
                                array $artifacts_to_select,
                                Tracker_Artifact $artifact = null, 
                                User $user,
                                $current_uri) {
        $hp = Codendi_HTMLPurifier::instance();
        
        $this->planning         = $planning;
        $this->planning_id      = $planning->getId();
        $this->planning_name    = $planning->getName();
        $this->planning_tracker = $planning->getPlanningTracker();
        
        if ($artifact) {
            $this->destination_id     = $artifact->getId();
            $this->destination_title  = $hp->purify($artifact->getTitle());
            $this->destination_link   = $artifact->getUri();
            $this->destination_xref   = $artifact->getXRef();
            
            // FIXME
            if ($this->destination_link == null) { $this->destination_link = ''; }
            if ($this->destination_xref == null) { $this->destination_xref = ''; }
        }
        $this->artifact            = $artifact;
        $this->artifacts_to_select = $artifacts_to_select;
        $this->content_view        = $content_view;
        $this->group_id            = $planning->getGroupId();
        $this->current_user        = $user;
        $this->current_uri         = $current_uri;
    }
    
    public function hasArtifact() {
        return $this->artifact !== null;
    }
    
    public function getLinkedItems($child_depth = 1) {
        $parent_node = new TreeNode(
            array(
                'id' => $this->destination_id,
            )
        );
        $parent_node->setId($this->destination_id);
        $this->addChildItem($this->artifact, $parent_node, $child_depth);
        
        $artifact_factory = Tracker_ArtifactFactory::instance();
        $hierarchy_factory = Tracker_Hierarchy_HierarchicalTrackerFactory::instance();
        $visitor = new Planning_ArtifactTreeNodeVisitor($artifact_factory, $hierarchy_factory, 'planning-draggable-alreadyplanned');
        $visitor->visit($parent_node);
        return $parent_node;
    }
    
    private function addChildItem($artifact, $parent_node, $child_depth = 0) {
        $linked_items = $artifact->getLinkedArtifactsFromHierarchy($this->current_user);
        if (! $linked_items) {
            return false;
        }
        foreach ($linked_items as $item) {
            $node = new TreeNode(
                array(
                    'id' => $item->getId(),
                )
            );
            $node->setId($item->getId());
            if ($child_depth > 0 ) {
                $this->addChildItem($item, $node, $child_depth - 1);
            }
            $parent_node->addChild($node);
        }
    }
    
    
    public function fetchSearchContent() {
        return $this->content_view->fetch();
    }
    
    public function pleaseChoose() {
        return $GLOBALS['Language']->getText('global', 'please_choose_dashed');
    }
    
    public function artifactsToSelect() {
        $hp = Codendi_HTMLPurifier::instance();
        $artifacts_data = array();
        foreach ($this->artifacts_to_select as $artifact) {
            $artifacts_data[] = array(
                'id'       => $artifact->getId(),
                'title'    => $hp->purify($artifact->getTitle()),
                'selected' => ($artifact->getId() == $this->destination_id) ? 'selected="selected"' : '',
            );
        }
        return $artifacts_data;
    }
    
    public function destinationHelp() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_destination_help');
    }
    
    public function getDestinationDroppableClass() {
        if ($this->canDrop()) {
            return 'planning-droppable';
        }
        return false;
    }
    
    public function backlogArtifactTypes() {
        $artifact_types = array();
        
        foreach($this->planning->getRootBacklogTrackers() as $tracker) {
            $artifact_types[] = array('name'        => $tracker->getItemName(),
                                      'creationUrl' => null);
        }
        
        return $artifact_types;
        
//        return array(
//            array('name' => 'Story', 'creationUrl' => '#create_story'),
//            array('name' => 'Bug',   'creationUrl' => '#create_bug'),
//        );
    }
    
    private function getArtifactCreationLabel(Tracker $tracker) {
        $new       = $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_artifact_new');
        $item_name = $tracker->getItemName();
        
        return "$new $item_name";
    }
    
    public function getPlanningTrackerArtifactCreationLabel() {
        return $this->getArtifactCreationLabel($this->planning_tracker);
    }
    
    public function getPlanningTrackerArtifactCreationUrl() {
        $tracker_id = $this->planning_tracker->getId();
        $return_url = urlencode($this->getCurrentUri());
        
        return TRACKER_BASE_URL."/?tracker=$tracker_id&func=new-artifact&return_to=$return_url";
    }
    
    public function canDrop() {
        if ($this->artifact) {
            $art_link_field = $this->artifact->getAnArtifactLinkField($this->current_user);
            if ($art_link_field && $art_link_field->userCanUpdate($this->current_user)) {
                return true;
            }
        }
        return false;
    }
    
    public function errorCantDrop() {
        if ($this->canDrop()) {
            return false;
        }
        return '<div class="feedback_warning">'. $GLOBALS['Language']->getText('plugin_tracker', 'must_have_artifact_link_field') .'</div>';
    }
    
    public function getCurrentUri() {
        return $this->current_uri;
    }
    
    public function createNewItemToPlan() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'create_new_item_to_plan');
    }
    
    public function editLabel() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'edit_item');
    }
    
    public function addNew() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'edit_item');
    }
    
    public function __trans($text) {
        $args = explode('|', $text);
        $secondary_key = array_shift($args);
        return $GLOBALS['Language']->getText('plugin_agiledashboard', $secondary_key, $args);
    }
}

?>
