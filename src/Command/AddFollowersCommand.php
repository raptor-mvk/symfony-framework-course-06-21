<?php

namespace App\Command;

use App\Service\SubscriptionService;
use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AddFollowersCommand extends Command
{
    /** @var int */
    public const OK = 0;
    /** @var int */
    public const GENERAL_ERROR = 1;

    private UserManager $userManager;

    private SubscriptionService $subscriptionService;

    public function __construct(UserManager $userManager, SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->userManager = $userManager;
        $this->subscriptionService = $subscriptionService;
    }

    protected function configure(): void
    {
        $this->setName('followers:add')
            ->setDescription('Adds followers to author')
            ->addArgument('authorId', InputArgument::REQUIRED, 'ID of author')
            ->addArgument('count', InputArgument::REQUIRED, 'How many followers should be added');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $authorId = (int)$input->getArgument('authorId');
        $user = $this->userManager->findUserById($authorId);
        if ($user === null) {
            $output->write("<error>User with ID $authorId doesn't exist</error>\n");
            return self::GENERAL_ERROR;
        }
        $count = (int)$input->getArgument('count');
        if ($count < 0) {
            $output->write("<error>Count should be positive integer</error>\n");
            return self::GENERAL_ERROR;
        }
        $result = $this->subscriptionService->addFollowers($user, "Reader #{$authorId}", $count);
        $output->write("<info>$result followers were created</info>\n");
        return self::OK;
    }
}
