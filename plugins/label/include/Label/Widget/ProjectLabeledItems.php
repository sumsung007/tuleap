<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Label\Widget;

use Codendi_HTMLPurifier;
use DataAccessException;
use Feedback;
use HTTPRequest;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Label\LabelDao;
use Widget;

class ProjectLabeledItems extends Widget
{
    const NAME = 'projectlabeleditems';

    /**
     * @var ProjectLabelBuilder
     */
    private $labels_presenter_builder;

    /**
     * @var ProjectLabelConfigRetriever
     */
    private $config_retriever;

    /**
     * @var ProjectLabelRequestDataValidator
     */
    private $project_data_validator;

    /**
     * @var ProjectLabelRetriever
     */
    private $labels_retriever;

    /**
     * @var Dao
     */
    private $dao;

    /**
     * @var \TemplateRenderer
     */
    private $renderer;

    public function __construct()
    {
        parent::__construct(self::NAME);

        $this->renderer = \TemplateRendererFactory::build()->getRenderer(
            LABEL_BASE_DIR . '/templates/widgets'
        );

        $this->dao = new Dao();
        $this->labels_retriever         = new ProjectLabelRetriever(new LabelDao());
        $this->project_data_validator   = new ProjectLabelRequestDataValidator();
        $this->config_retriever         = new ProjectLabelConfigRetriever(new ProjectLabelConfigDao());
        $this->labels_presenter_builder = new ProjectLabelBuilder();
    }

    public function loadContent($id)
    {
        $this->content_id = $id;
    }

    public function getTitle()
    {
        return dgettext('tuleap-label', 'Labeled Items');
    }

    public function hasCustomTitle()
    {
        return $this->getPurifiedCustomTitle() !== "";
    }

    public function getPurifiedCustomTitle()
    {
        $config_labels = $this->config_retriever->getLabelsConfig($this->content_id);

        return Codendi_HTMLPurifier::instance()->purify(
            $this->renderer->renderToString(
                'project-labeled-items-config',
                array('labels' => $config_labels)
            ),
            CODENDI_PURIFIER_FULL
        );
    }

    public function getDescription()
    {
        return dgettext('tuleap-label', 'Displays items with configured labels in the project. For example you can search all Pull Requests labeled "Emergency" and "v3.0"');
    }

    public function isUnique()
    {
        return false;
    }

    public function getContent()
    {
        $config_labels = $this->config_retriever->getLabelsConfig($this->content_id);
        $project       = $this->getProject();
        $request       = HTTPRequest::instance();

        return $this->renderer->renderToString(
            'project-labeled-items',
            new ProjectLabeledItemsPresenter($project, $request->getCurrentUser(), $config_labels)
        );
    }

    public function getPreferences($widget_id)
    {
        $selected_labels = $this->getProjectSelectedLabelsPresenter();
        $project_id      = $this->getProject()->getID();

        return $this->renderer->renderToString(
            'project-label-selector',
            new ProjectLabelSelectorPresenter($project_id, $selected_labels)
        );
    }

    public function getInstallPreferences()
    {
        $selected_labels = array();
        $project_id      = $this->getProject()->getID();

        return $this->renderer->renderToString(
            'project-label-selector',
            new ProjectLabelSelectorPresenter($project_id, $selected_labels)
        );
    }

    public function create(&$request)
    {
        $this->content_id = $this->dao->create();

        $project_labels = $this->getProjectLabels();
        $this->storeLabelsConfiguration($request, $project_labels);

        return $this->content_id;
    }


    public function updatePreferences(&$request)
    {
        $project_labels = $this->getProjectAllLabelsPresenter();
        $this->storeLabelsConfiguration($request, $project_labels);
    }

    private function storeLabelsConfiguration(HTTPRequest $request, $project_labels)
    {
        try {
            $this->project_data_validator->validateDataFromRequest($request, $project_labels);
            $this->dao->storeLabelsConfiguration($this->content_id, $request->get('project-labels'));
        } catch (ProjectLabelDoesNotBelongToProjectException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-label', 'Error: label does not belong to project or does not exist.')
            );
        } catch (ProjectLabelAreNotValidException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-label', 'Error: one projects label is invalid.')
            );
        } catch (ProjectLabelAreMandatoryException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-label', 'Error: you should specify at least one project label.')
            );
        } catch (DataAccessException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-label', 'An exception occurred while saving data.')
            );
        }
    }

    private function getProjectSelectedLabelsPresenter()
    {
        $project_labels = $this->getProjectLabels();
        $config_labels  = $this->getConfigLabels();

        $labels = $this->labels_presenter_builder->buildSelectedLabels($project_labels, $config_labels);

        return $labels;
    }

    private function getProjectAllLabelsPresenter()
    {
        $project_labels = $this->getProjectLabels();
        $config_labels  = $this->getConfigLabels();

        $labels = $this->labels_presenter_builder->build($project_labels, $config_labels);

        return $labels;
    }

    private function getConfigLabels()
    {
        return $this->config_retriever->getLabelsConfig($this->content_id);
    }

    private function getProjectLabels()
    {
        $project        = $this->getProject();
        $project_labels = $this->labels_retriever->getLabelsByProject($project);

        return $project_labels;
    }

    public function destroy($id)
    {
        $this->dao->removeLabelByContentId($id);
    }

    public function getJavascriptDependencies()
    {
        $labeled_items_include_assets = new IncludeAssets(
            LABEL_BASE_DIR . '/www/assets',
            LABEL_BASE_URL . '/assets'
        );

        return array(
            array('file' => $labeled_items_include_assets->getFileURL('widget-project-labeled-items.js'))
        );
    }

    /**
     * @return \Project
     */
    private function getProject()
    {
        return HTTPRequest::instance()->getProject();
    }
}