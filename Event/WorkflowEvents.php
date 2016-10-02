<?php

namespace Dergipark\WorkflowBundle\Event;

use Ojs\CoreBundle\Events\EventDetail;
use Ojs\CoreBundle\Events\MailEventsInterface;

final class WorkflowEvents implements MailEventsInterface
{
    const WORKFLOW_STARTED = 'dp.workflow.started';

    const REVIEW_FORM_RESPONSE = 'dp.workflow.form.response';

    const REVIEW_FORM_RESPONSE_PREVIEW = 'dp.workflow.form.response.preview';

    const REVIEW_FORM_REQUEST = 'dp.workflow.form.request';

    const WORKFLOW_GRANT_USER = 'dp.workflow.grant.user';

    const DIALOG_POST_COMMENT = 'dp.workflow.dialog.post.comment';

    const DIALOG_POST_FILE = 'dp.workflow.dialog.post.file';

    const CREATE_SPESIFIC_DIALOG = 'dp.workflow.create.specific.dialog';

    const CREATE_DIALOG_WITH_AUTHOR = 'dp.workflow.dialog.with.author';

    const CREATE_BASIC_DIALOG= 'dp.workflow.basic.dialog';

    const STEP_GOTO_ARRANGEMET = 'dp.workflow.step.goto.arrangement';

    const STEP_GOTO_REVIEWING = 'dp.workflow.step.goto.reviewing';

    const ACCEPT_SUBMISSION_DIRECTLY = 'dp.workflow.accept.submission.directly';

    const WORKFLOW_FINISH_ACTION = 'dp.workflow.finish.action';

    const DECLINE_SUBMISSION = 'dp.workflow.decline.submission';

    const CLOSE_DIALOG = 'dp.workflow.close.dialog';

    const REOPEN_DIALOG = 'dp.workflow.reopen.dialog';

    const REMOVE_DIALOG = 'dp.workflow.remove.dialog';

    const REVIEWER_INVITE = 'dp.workflow.reviewer.invite';

    const REVIEWER_REMIND = 'dp.workflow.reviewer.remind';

    const ACCEPT_REVIEW = 'dp.workflow.accept.review';

    const REJECT_REVIEW = 'dp.workflow.reject.review';

    public function getMailEventsOptions()
    {
        return [
            new EventDetail(self::WORKFLOW_STARTED, 'admin', [
                'article.author', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title',
            ]),
            new EventDetail(self::REVIEW_FORM_RESPONSE, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'dialog.title', 'form.name',
            ]),
            new EventDetail(self::REVIEW_FORM_RESPONSE_PREVIEW, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'dialog.title', 'form.name',
            ]),
            new EventDetail(self::REVIEW_FORM_REQUEST, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'dialog.title', 'form.name'
            ]),
            new EventDetail(self::WORKFLOW_GRANT_USER, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title',
            ]),
            new EventDetail(self::DIALOG_POST_COMMENT, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'dialog.title', 'post.content'
            ]),
            new EventDetail(self::DIALOG_POST_FILE, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'dialog.title', 'file.name',
            ]),
            new EventDetail(self::CREATE_SPESIFIC_DIALOG.'.assign.section.editor', 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'dialog.title',
            ]),
            new EventDetail(self::CREATE_SPESIFIC_DIALOG, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'dialog.title',
            ]),
            new EventDetail(self::CREATE_DIALOG_WITH_AUTHOR, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title',
            ]),
            new EventDetail(self::CREATE_BASIC_DIALOG, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'dialog.title',
            ]),
            new EventDetail(self::STEP_GOTO_ARRANGEMET, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title',
            ]),
            new EventDetail(self::STEP_GOTO_REVIEWING, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title',
            ]),
            new EventDetail(self::ACCEPT_SUBMISSION_DIRECTLY, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title',
            ]),
            new EventDetail(self::WORKFLOW_FINISH_ACTION, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title',
            ]),
            new EventDetail(self::DECLINE_SUBMISSION, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title',
            ]),
            new EventDetail(self::CLOSE_DIALOG, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'dialog.title',
            ]),
            new EventDetail(self::REOPEN_DIALOG, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'dialog.title',
            ]),
            new EventDetail(self::REMOVE_DIALOG, 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'dialog.title',
            ]),
            new EventDetail(self::REVIEWER_INVITE.'.to.editor', 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title',
            ]),
            new EventDetail(self::REVIEWER_INVITE.'.to.reviewer', 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'accept.link', 'reject.link',
                'dayLimit', 'article.abstract', 'article.authors',
            ]),
            new EventDetail(self::REVIEWER_REMIND.'.to.editor', 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title',
            ]),
            new EventDetail(self::REVIEWER_REMIND.'.to.reviewer', 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'accept.link', 'reject.link',
            ]),
            new EventDetail(self::ACCEPT_REVIEW.'.to.editor', 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'reviewer.username', 'reviewer.fullName',
            ]),
            new EventDetail(self::ACCEPT_REVIEW.'.to.reviewer', 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title',
            ]),
            new EventDetail(self::REJECT_REVIEW.'.to.editor', 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title', 'reviewer.username', 'reviewer.fullName',
            ]),
            new EventDetail(self::REJECT_REVIEW.'.to.reviewer', 'admin', [
                'done.by', 'related.link', 'journal', 'receiver.username', 'receiver.fullName', 'article.title',
            ]),
        ];
    }
}
