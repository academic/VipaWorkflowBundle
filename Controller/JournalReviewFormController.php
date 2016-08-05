<?php

namespace Dergipark\WorkflowBundle\Controller;

use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Source\Entity;
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
     * Creates a new ArticleFile entity.
     * @param  Request $request
     * @param  integer $stepId
     * @return RedirectResponse|Response
     *
     */
    public function createAction(Request $request, $stepId)
    {
        $journalService = $this->get('ojs.journal_service');
        $journal = $journalService->getSelectedJournal();
        $em = $this->getDoctrine()->getManager();

        if (!$this->isGranted('EDIT', $journal, 'articles')) {
            throw new AccessDeniedException("You not authorized for this page!");
        }

        /** @var Article $step */
        $step = $em->getRepository('OjsJournalBundle:Article')->find($stepId);
        $this->throw404IfNotFound($step);

        $entity = new ArticleFile();
        $form = $this->createCreateForm($entity, $journal, $step)
                     ->add('create', 'submit', ['label' => 'c']);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entity->setArticle($step);
            $em->persist($entity);
            $em->flush();

            $this->successFlashBag('successful.create');

            return $this->redirect(
                $this->generateUrl(
                    'ojs_journal_article_file_index',
                    ['articleId' => $step->getId(), 'journalId' => $journal->getId()]
                )
            );
        }

        return $this->render(
            'OjsJournalBundle:ArticleFile:new.html.twig',
            [
                'entity' => $entity,
                'form'   => $form->createView(),
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
        $journalService = $this->get('ojs.journal_service');
        $em = $this->getDoctrine()->getManager();
        /** @var Article $step */
        $step = $em->getRepository(JournalWorkflowStep::class)->find($stepId);

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

        $step = $em->getRepository('OjsJournalBundle:Article')->find($stepId);
        $this->throw404IfNotFound($step);

        /** @var ArticleFile $entity */
        $entity = $em->getRepository('OjsJournalBundle:ArticleFile')->findOneBy(
            [
                'article' => $step,
                'id'      => $id,
            ]
        );

        $this->throw404IfNotFound($entity);

        if (!$this->isGranted('VIEW', $journal, 'articles')) {
            throw new AccessDeniedException("You not authorized for this page!");
        }

        $type = ArticleFileParams::fileType($entity->getType());

        $token = $this
            ->get('security.csrf.token_manager')
            ->refreshToken('ojs_journal_article_file'.$entity->getId());

        return $this->render(
            'OjsJournalBundle:ArticleFile:show.html.twig',
            [
                'entity' => $entity,
                'type'   => $type,
                'token'  => $token,
            ]
        );
    }

    /**
     * Displays a form to edit an existing ArticleFile entity.
     * @param integer $id
     * @param integer $stepId
     * @return Response
     */
    public function editAction($id, $stepId)
    {
        $journalService = $this->get('ojs.journal_service');
        $journal = $journalService->getSelectedJournal();
        $em = $this->getDoctrine()->getManager();

        if (!$this->isGranted('EDIT', $journal, 'articles')) {
            throw new AccessDeniedException("You not authorized for this page!");
        }

        /** @var Article $step */
        $step = $em->getRepository('OjsJournalBundle:Article')->find($stepId);
        $this->throw404IfNotFound($step);

        /** @var ArticleFile $entity */
        $entity = $em->getRepository('OjsJournalBundle:ArticleFile')->findOneBy(
            [
                'article' => $step,
                'id'      => $id,
            ]
        );

        $this->throw404IfNotFound($entity);

        $editForm = $this->createEditForm($entity, $journal, $step)
                         ->add('save', 'submit', ['label' => 'save']);

        $token = $this
            ->get('security.csrf.token_manager')
            ->refreshToken('ojs_journal_article_file'.$entity->getId());

        return $this->render(
            'OjsJournalBundle:ArticleFile:edit.html.twig',
            [
                'entity'    => $entity,
                'edit_form' => $editForm->createView(),
                'token'     => $token,
            ]
        );
    }

    /**
     * Creates a form to edit a ArticleFile entity.
     * @param ArticleFile $entity The entity
     * @param Journal $journal
     * @param Article $step
     * @return Form The form
     */
    private function createEditForm(ArticleFile $entity, Journal $journal, Article $step)
    {
        $form = $this->createForm(
            new ArticleFileType(),
            $entity,
            [
                'action'  => $this->generateUrl(
                    'ojs_journal_article_file_update',
                    ['id' => $entity->getId(), 'journalId' => $journal->getId(), 'articleId' => $step->getId()]
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

        if (!$this->isGranted('EDIT', $journal, 'articles')) {
            throw new AccessDeniedException("You not authorized for this page!");
        }

        /** @var Article $step */
        $step = $em->getRepository('OjsJournalBundle:Article')->find($stepId);
        $this->throw404IfNotFound($step);

        /** @var ArticleFile $entity */
        $entity = $em->getRepository('OjsJournalBundle:ArticleFile')->findOneBy(
            [
                'article' => $step,
                'id'      => $id,
            ]
        );
        $this->throw404IfNotFound($entity);

        $editForm = $this->createEditForm($entity, $journal, $step)
                         ->add('save', 'submit', ['label' => 'save']);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            $this->successFlashBag('successful.update');

            return $this->redirect(
                $this->generateUrl(
                    'ojs_journal_article_file_edit',
                    ['id' => $id, 'journalId' => $journal->getId(), 'articleId' => $step->getId()]
                )
            );
        }

        return $this->render(
            'OjsJournalBundle:ArticleFile:edit.html.twig',
            [
                'entity'    => $entity,
                'edit_form' => $editForm->createView(),
            ]
        );
    }

    /**
     * Deletes a ArticleFile entity.
     * @param  Request $request
     * @param  integer $id
     * @param  integer $stepId
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, $id, $stepId)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $em = $this->getDoctrine()->getManager();
        if (!$this->isGranted('EDIT', $journal, 'articles')) {
            throw new AccessDeniedException("You not authorized for this page!");
        }

        /** @var Article $step */
        $step = $em->getRepository('OjsJournalBundle:Article')->find($stepId);
        $this->throw404IfNotFound($step);

        /** @var ArticleFile $entity */
        $entity = $em->getRepository('OjsJournalBundle:ArticleFile')->findOneBy(
            [
                'article' => $step,
                'id'      => $id,
            ]
        );

        $this->throw404IfNotFound($entity);

        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->getToken('ojs_journal_article_file'.$entity->getId());

        if ($token != $request->get('_token')) {
            throw new TokenNotFoundException("Token Not Found!");
        }
        $this->get('ojs_core.delete.service')->check($entity);
        $em->remove($entity);
        $em->flush();
        $this->successFlashBag('successful.remove');

        return $this->redirectToRoute(
            'ojs_journal_article_file_index',
            ['articleId' => $entity->getArticle()->getId(), 'journalId' => $journal->getId()]
        );
    }
}
