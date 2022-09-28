<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Solution Libre SAS
 * @copyright Copyright (c) 2018 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Florent Poinsaut <florent@solution-libre.fr>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\ShareListing\Command;

use OCA\ShareListing\Service\ReportSender;
use OCA\ShareListing\Service\SharesList;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Share\IManager as ShareManager;
use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendShares extends Base {

	/** @var ShareManager */
	private $shareManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var ReportSender */
	private $reportSender;

	/** @var SharesList */
	private $sharesList;

	public function __construct(
		ShareManager $shareManager,
		IUserManager $userManager,
		IRootFolder $rootFolder,
		ReportSender $reportSender,
		SharesList $sharesList
	) {
		parent::__construct();

		$this->shareManager = $shareManager;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->reportSender = $reportSender;
		$this->sharesList = $sharesList;
	}

	public function configure() {
		$this->setName('sharing:send')
			->setDescription('Send list who has access to shares by owner')
			->addOption(
				'recipients',
				'r',
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Email recipients'
			)
			->addOption(
				'user',
				'u',
				InputOption::VALUE_OPTIONAL,
				'Will list shares of the given user'
			)
			->addOption(
				'path',
				'p',
				InputOption::VALUE_OPTIONAL,
				'Will only consider the given path'
			)->addOption(
				'token',
				't',
				InputOption::VALUE_OPTIONAL,
				'Will only consider the given token'
			)->addOption(
				'filter',
				'f',
				InputOption::VALUE_OPTIONAL,
				'Filter shares, possible values: owner, initiator, recipient, token, has-expiration, no-expiration'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->checkAllRequiredOptionsAreNotEmpty($input);

		$user = $input->getOption('user');
		$path = $input->getOption('path');
		$token = $input->getOption('token');
		$filter = $this->sharesList->filterStringToInt($input->getOption('filter'));
		$recipients = $input->getOption('recipients');

		$this->reportSender->sendReport(
			$recipients,
			$user,
			$filter,
			$path,
			$token
		);

		return 0;
	}

	private function checkAllRequiredOptionsAreNotEmpty(InputInterface $input)
    {
        $errors = [];
        $recipients = $this->getDefinition()->getOption('recipients');

        /** @var InputOption $recipient */
        foreach ([$recipients] as $recipient) {
            $name = $recipient->getName();
            $value = $input->getOption($name);

            if ($value === null || $value === '' || ($recipient->isArray() && empty($value))) {
                $errors[] = sprintf('The required option --%s is not set or is empty', $name);
            }
        }

        if (count($errors)) {
            throw new \InvalidArgumentException(implode("\n\n", $errors));
        }
    }
}