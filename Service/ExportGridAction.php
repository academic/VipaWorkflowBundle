<?php

namespace Dergipark\WorkflowBundle\Service;

use APY\DataGridBundle\Grid\Action\RowAction;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ExportGridAction
 * @package Dergipark\WorkflowBundle\Service
 */
class ExportGridAction
{
    /**
     * @var  TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    /**
     * @param int $journalId
     * @return RowAction
     */
    public function printSingleReviewResponse($journalId)
    {
        $rowAction = new RowAction('print.or.save', 'dp_workflow_export_review_form_single_print');
        $rowAction->setAttributes(
            [
                'class' => 'btn btn-info btn-xs ',
                'data-toggle' => 'tooltip',
                'title' => $this->translator->trans('print.or.save'),
            ]
        );
        $rowAction->setTarget('_blank');
        $rowAction->setRouteParameters(['id', 'journalId' => $journalId]);
        return $rowAction;
    }
}
