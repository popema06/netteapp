<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Database\Context;


final class HomepagePresenter extends Nette\Application\UI\Presenter
{
    /** @var Context */
    private $database;

    public function __construct(Context $database)
    {
        parent::__construct();
        $this->database = $database;
    }

    public function renderDefault(): void
    {
        $this->template->posts = $this->database->table('posts')
            ->order('created_at DESC')
            ->limit(5);
    }

}
