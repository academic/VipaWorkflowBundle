<?php

namespace Dergipark\WorkflowBundle\Controller;

use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Source\Entity;
use Dergipark\WorkflowBundle\Entity\ArticleWorkflowStep;
use Dergipark\WorkflowBundle\Entity\JournalReviewForm;
use Dergipark\WorkflowBundle\Entity\JournalWorkflowStep;
use Dergipark\WorkflowBundle\Form\Type\JournalReviewFormType;
use Doctrine\ORM\QueryBuilder;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Ojs\CoreBundle\Params\ArticleFileParams;
use Ojs\JournalBundle\Entity\Article;
use Ojs\JournalBundle\Entity\ArticleFile;
use Ojs\JournalBundle\Entity\Journal;
use Ojs\JournalBundle\Form\Type\ArticleFileType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

/**
 * Class JournalReviewFormController
 * @package Dergipark\WorkflowBundle\Controller
 */
class JournalReviewFormController extends Controller
{
    /**
     * @param int $stepId
     * @return Response
     */
    public function indexAction($stepId)
    {
        $permisionService = $this->get('dp.workflow_permission_service');
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $em = $this->getDoctrine()->getManager();
        if(!$permisionService->isHaveEditorRole()){
            throw new AccessDeniedException;
        }
        $step = $em->getRepository(JournalWorkflowStep::class)->find($stepId);
        $this->throw404IfNotFound($step);

        $source = new Entity('DergiparkWorkflowBundle:JournalReviewForm');
        $tableAlias = $source->getTableAlias();
        $source->manipulateQuery(
            function (QueryBuilder $qb) use ($step, $tableAlias) {
                return $qb
                    ->where($tableAlias.'.step = :step')
                    ->setParameter('step', $step);
            }
        );

        $grid = $this->get('grid')->setSource($source);
        $gridAction = $this->get('grid_action');
        $actionColumn = new ActionsColumn("actions", 'actions');
        $rowAction[] = $gridAction->showAction(
            'dp_workflow_review_form_show',
            ['id', 'journalId' => $journal->getId(), 'stepId' => $stepId]
        );
        $rowAction[] = $gridAction->editAction(
            'dp_workflow_review_form_edit',
            ['id', 'journalId' => $journal->getId(), 'stepId' => $stepId]
        );
        $rowAction[] = $gridAction->deleteAction(
            'dp_workflow_review_form_delete',
            ['id', 'journalId' => $journal->getId(), 'stepId' => $stepId]
        );

        $actionColumn->setRowActions($rowAction);
        $grid->addColumn($actionColumn);

        $data = [];
        $data['grid'] = $grid;
        $data['step'] = $step;

        return $grid->getGridResponse('DergiparkWorkflowBundle:JournalReviewForm:index.html.twig', $data);
    }

    /**
     * @param Request $request
     * @param $stepId
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request, $stepId)
    {
        $journalService = $this->get('ojs.journal_service');
        $journal = $journalService->getSelectedJournal();
        $em = $this->getDoctrine()->getManager();

        /** @var JournalWorkflowStep $step */
        $step = $em->getRepository(JournalWorkflowStep::class)->find($stepId);
        $this->throw404IfNotFound($step);

        $entity = new JournalReviewForm();
        $form = $this->createCreateForm($entity, $step)
                     ->add('create', 'submit', ['label' => 'c']);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entity->setStep($step);
            $em->persist($entity);
            $em->flush();

            $this->successFlashBag('successful.create');

            return $this->redirect(
                $this->generateUrl(
                    'dp_workflow_review_form',
                    [
                        'stepId' => $step->getId(),
                        'journalId' => $journal->getId()
                    ]
                )
            );
        }

        return $this->render(
            'DergiparkWorkflowBundle:JournalReviewForm:new.html.twig',
            [
                'entity'    => $entity,
                'form'      => $form->createView(),
                'step'      => $step,
            ]
        );
    }

    /**
     * @param JournalReviewForm $entity
     * @param JournalWorkflowStep $step
     * @return Form
     */
    private function createCreateForm(JournalReviewForm $entity, JournalWorkflowStep $step)
    {
        $form = $this->createForm(
            new JournalReviewFormType(),
            $entity,
            [
                'action'  => $this->generateUrl(
                    'dp_workflow_review_form_create',
                    [
                        'journalId' => $step->getJournal()->getId(),
                        'stepId' => $step->getId()
                    ]
                ),
                'method'  => 'POST',
            ]
        );

        return $form;
    }

    /**
     * @param $stepId
     * @return Response
     */
    public function newAction($stepId)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var JournalWorkflowStep $step */
        $step = $em->getRepository(JournalWorkflowStep::class)->find($stepId);
        $this->throw404IfNotFound($step);

        $entity = new JournalReviewForm();
        $entity->setStep($step);
        $form = $this->createCreateForm($entity, $step)
            ->add('create', 'submit', ['label' => 'c']);

        return $this->render(
            'DergiparkWorkflowBundle:JournalReviewForm:new.html.twig',
            [
                'entity'    => $entity,
                'form'      => $form->createView(),
                'step'      => $step,
            ]
        );
    }

    /**
     * Finds and displays a ArticleFile entity.
     * @param integer $id
     * @param integer $stepId
     * @return Response
     */
    public function showAction($id, $stepId)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $em = $this->getDoctrine()->getManager();

        $step = $em->getRepository(ArticleWorkflowStep::class)->find($stepId);
        $this->throw404IfNotFound($step);

        /** @var ArticleFile $entity */
        $entity = $em->getRepository(JournalReviewForm::class)->findOneBy(
            [
                'step'  => $step,
                'id'    => $id,
            ]
        );

        $this->throw404IfNotFound($entity);

        $token = $this
            ->get('security.csrf.token_manager')
            ->refreshToken('dp_workflow_review_form'.$entity->getId());

        return $this->render(
            'DergiparkWorkflowBundle:JournalReviewForm:show.html.twig',
            [
                'entity' => $entity,
                'token'  => $token,
            ]
        );
    }

    /**
     * @param $id
     * @param $stepId
     * @return Response
     */
    public function editAction($id, $stepId)
    {
        $journalService = $this->get('ojs.journal_service');
        $journal = $journalService->getSelectedJournal();
        $em = $this->getDoctrine()->getManager();

        /** @var JournalWorkflowStep $step */
        $step = $em->getRepository(JournalWorkflowStep::class)->find($stepId);
        $this->throw404IfNotFound($step);

        /** @var JournalReviewForm $entity */
        $entity = $em->getRepository(JournalReviewForm::class)->findOneBy(
            [
                'step' => $step,
                'id'      => $id,
            ]
        );
        $this->throw404IfNotFound($entity);

        $editForm = $this->createEditForm($entity, $step)
            ->add('save', 'submit', ['label' => 'save']);

        $token = $this
            ->get('security.csrf.token_manager')
            ->refreshToken('dp_workflow_review_form'.$entity->getId());

        return $this->render(
            'DergiparkWorkflowBundle:JournalReviewForm:edit.html.twig',
            [
                'entity'    => $entity,
                'edit_form' => $editForm->createView(),
                'token'     => $token,
            ]
        );
    }

    /**
     * @param JournalReviewForm $entity
     * @param JournalWorkflowStep $step
     * @return Form
     */
    private function createEditForm(JournalReviewForm $entity, JournalWorkflowStep $step)
    {
        $form = $this->createForm(
            new JournalReviewFormType(),
            $entity,
            [
                'action'  => $this->generateUrl(
                    'dp_workflow_review_form_update',
                    [
                        'id' => $entity->getId(),
                        'journalId' => $step->getJournal()->getId(),
                        'stepId' => $step->getId()
                    ]
                ),
                'method'  => 'PUT',
            ]
        );

        return $form;
    }

    /**
     * Edits an existing ArticleFile entity.
     * @param  Request $request
     * @param integer $id
     * @param integer $stepId
     * @return RedirectResponse|Response
     *
     */
    public function updateAction(Request $request, $id, $stepId)
    {
        $journalService = $this->get('ojs.journal_service');
        $journal = $journalService->getSelectedJournal();
        $em = $this->getDoctrine()->getManager();

        /** @var JournalWorkflowStep $step */
        $step = $em->getRepository(JournalWorkflowStep::class)->find($stepId);
        $this->throw404IfNotFound($step);

        /** @var JournalReviewForm $entity */
        $entity = $em->getRepository(JournalReviewForm::class)->findOneBy(
            [
                'step' => $step,
                'id'      => $id,
            ]
        );
        $this->throw404IfNotFound($entity);

        $editForm = $this->createEditForm($entity, $step)
            ->add('save', 'submit', ['label' => 'save']);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            $this->successFlashBag('successful.update');

            return $this->redirect(
                $this->generateUrl(
                    'dp_workflow_review_form_edit',
                    [
                        'id' => $id,
                        'journalId' => $journal->getId(),
                        'stepId' => $step->getId()
                    ]
                )
            );
        }

        return $this->render(
            'DergiparkWorkflowBundle:JournalReviewForm:edit.html.twig',
            [
                'entity'    => $entity,
                'edit_form' => $editForm->createView(),
            ]
        );
    }

    /**
     * @param Request $request
     * @param $id
     * @param $stepId
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, $id, $stepId)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $em = $this->getDoctrine()->getManager();

        /** @var JournalWorkflowStep $step */
        $step = $em->getRepository(JournalWorkflowStep::class)->find($stepId);
        $this->throw404IfNotFound($step);

        /** @var JournalReviewForm $entity */
        $entity = $em->getRepository(JournalReviewForm::class)->findOneBy(
            [
                'step'  => $step,
                'id'    => $id,
            ]
        );

        $this->throw404IfNotFound($entity);

        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->getToken('dp_workflow_review_form'.$entity->getId());

        if ($token != $request->get('_token')) {
            throw new TokenNotFoundException("Token Not Found!");
        }
        $this->get('ojs_core.delete.service')->check($entity);
        $em->remove($entity);
        $em->flush();
        $this->successFlashBag('successful.remove');

        return $this->redirectToRoute(
            'dp_workflow_review_form',
            [
                'stepId' => $entity->getStep()->getId(),
                'journalId' => $journal->getId(),
            ]
        );
    }
}
