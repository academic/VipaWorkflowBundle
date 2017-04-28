<?php

namespace Vipa\WorkflowBundle\Controller;

use APY\DataGridBundle\Grid\Action\MassAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Source\Entity;
use Doctrine\ORM\QueryBuilder;
use Vipa\CoreBundle\Controller\VipaController as Controller;
use Vipa\WorkflowBundle\Entity\DialogPost;
use Vipa\WorkflowBundle\Params\DialogPostTypes;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ExportReviewFormController
 * @package Vipa\WorkflowBundle\Controller
 */
class ExportReviewFormController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        $translator = $this->get('translator');
        $grid = $this->get('grid');
        $currentUser = $this->getUser();
        $journal = $this->get('vipa.journal_service')->getSelectedJournal();
        $permissionService = $this->get('dp.workflow_permission_service');
        $isEditor = false;
        if($permissionService->isHaveEditorRole()){
            $isEditor = true;
        }
        $source = new Entity(DialogPost::class);
        $tableAlias = $source->getTableAlias();
        $source->manipulateQuery(
            function (QueryBuilder $qb) use ($tableAlias, $isEditor, $currentUser, $journal) {
                $qb
                    ->join($tableAlias.'.dialog','d')
                    ->join('d.step','ws')
                    ->join('ws.articleWorkflow','aw')
                    ->andWhere('aw.journal = :journal')
                    ->setParameter('journal', $journal->getId())
                    ->andWhere($tableAlias.'.type = :formResponseType')
                    ->setParameter('formResponseType', DialogPostTypes::TYPE_FORM_RESPONSE);

                if (!$isEditor) {
                    $qb
                        ->andWhere($tableAlias.'.sendedBy = :currentUser')
                        ->setParameter('currentUser', $currentUser);
                }

                return $qb;
            }
        );

        $grid->setSource($source);

        //setup mass actions
        $exportFormResponseAction = new MassAction($translator->trans('print.or.save'), [
            $this, 'massFormResponsePrint'
        ]);
        $grid->addMassAction($exportFormResponseAction);

        //setup sing article export actions
        $exportGridAction = $this->get('dp.workflow_export_grid_action');
        $actionColumn = new ActionsColumn("actions", 'actions');
        $rowAction[] = $exportGridAction->printSingleReviewResponse($journal->getId());
        $actionColumn->setRowActions($rowAction);
        $grid->addColumn($actionColumn);

        return $grid->getGridResponse('VipaWorkflowBundle:ExportReviewForm:index.html.twig', [
            'grid'      => $grid,
            'journal'   => $journal,
        ]);
    }

    /**
     * @param $primaryKeys
     * @return Response
     */
    public function massFormResponsePrint($primaryKeys)
    {
        $journalService = $this->get('vipa.journal_service');
        $journal = $journalService->getSelectedJournal();
        if(count($primaryKeys) < 1){
            $this->errorFlashBag('you.must.select.one.least.element');
            return $this->redirectToRoute('dp_workflow_export_review_form', [
                'journalId' => $journal->getId(),
            ]);
        }
        $em = $this->getDoctrine()->getManager();
        $dialogPostRepo = $em->getRepository(DialogPost::class);
        $formResponses = $dialogPostRepo->findById($primaryKeys);

        return $this->render('VipaWorkflowBundle:ExportReviewForm:print_or_save.html.twig', [
            'formResponses' => $formResponses,
            'journal' => $journal,
        ]);
    }

    /**
     * @param DialogPost $dialogPost
     * @return Response
     */
    public function singlePrintAction(DialogPost $dialogPost)
    {
        $journalService = $this->get('vipa.journal_service');
        $journal = $journalService->getSelectedJournal();

        return $this->render('VipaWorkflowBundle:ExportReviewForm:print_or_save.html.twig', [
            'formResponses' => [$dialogPost],
            'journal' => $journal,
        ]);
    }
}
