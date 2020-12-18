<?php


namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Database\Context;
use Tracy\Debugger;

class PostPresenter extends Nette\Application\UI\Presenter
{
    /** @var Context */
    private $database;

    public function __construct(Context $database)
    {
        parent::__construct();
        $this->database = $database;
    }


    protected function createComponentCommentForm(): Form
    {
        $form = new Form; // means Nette\Application\UI\Form

        $form->addText('name', 'Jméno:')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired('Prosím doplňte jméno.');

        $form->addEmail('email', 'E-mail:')
            ->setHtmlAttribute('class', 'form-control');

        $form->addTextArea('content', 'Komentář:')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired('Prosím doplňte komentář.');

        $form->addSubmit('send', 'Publikovat komentář')
            ->setHtmlAttribute('class', 'btn btn-info');

        $form->onSuccess[] = [$this, 'commentFormSucceeded'];

        return $form;
    }

    public function commentFormSucceeded(Form $form, \stdClass $values): void
    {
        $postId = $this->getParameter('postId');

        $this->database->table('comments')->insert([
            'post_id' => $postId,
            'name' => $values->name,
            'email' => $values->email,
            'content' => $values->content,
        ]);

        $this->flashMessage('Děkuji za komentář', 'success');
        $this->redirect('this');
    }


    protected function createComponentPostForm(): Form
    {
        $form = new Form;
        $form->addText('title', 'Titulek:')
            ->setRequired();
        $form->addTextArea('content', 'Obsah:')
            ->setRequired();

        $form->addSubmit('send', 'Uložit a publikovat');
        $form->onSuccess[] = [$this, 'postFormSucceeded'];

        return $form;
    }

    public function postFormSucceeded(Form $form, array $values): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.');
        }

        $postId = $this->getParameter('postId');

        if ($postId) {
            $post = $this->database->table('posts')->get($postId);
            $post->update($values);
        } else {
            $post = $this->database->table('posts')->insert($values);
        }

        $this->flashMessage("Příspěvek byl úspěšně publikován.", 'success');
        $this->redirect('show', $post->id);
    }

    public function actionEdit(int $postId): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }

        $post = $this->database->table('posts')->get($postId);
        if (!$post) {
            $this->error('Příspěvek nebyl nalezen');
        }
        $this['postForm']->setDefaults($post->toArray());
    }

    public function actionCreate(): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }



    public function renderShow(int $postId): void
    {
        $post = $this->database->table('posts')->get($postId);
        if (!$post) {
            $this->flashMessage('Neplatná stránka');
            Debugger::log('Wrong ID: '.$postId, 'post.id.error');
            $this->redirect('Homepage:default');
        }

        $this->template->post = $post;
        $this->template->comments = $post->related('comments')->order('created_at');
        Debugger::barDump($this->template->comments, 'comments');
    }
}
