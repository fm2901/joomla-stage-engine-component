<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_stageengine
 */

namespace Joomla\Component\Stageengine\Site\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use StageEngine\Application\StageResolver;
use StageEngine\Application\StageTransitionService;
use StageEngine\Domain\Exceptions\DomainException;
use StageEngine\Domain\Stage;
use StageEngine\Infrastructure\Joomla\JoomlaBillingRepository;
use StageEngine\Infrastructure\Joomla\JoomlaCertificateRepository;
use StageEngine\Infrastructure\Joomla\JoomlaCompanyRepository;
use StageEngine\Infrastructure\Joomla\JoomlaEventLogRepository;

require_once JPATH_COMPONENT . '/lib/bootstrap.php';

final class HtmlView extends BaseHtmlView
{
    public object $company;
    public array $companies = [];
    public string $resolvedStage;
    public array $history = [];
    public array $availableTransitions = [];
    public array $instructions = [];
    public bool $isActivated = false;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $db = Factory::getContainer()->get('DatabaseDriver');

        $companies = new JoomlaCompanyRepository($db);
        $events = new JoomlaEventLogRepository($db);
        $billing = new JoomlaBillingRepository($db);
        $certs = new JoomlaCertificateRepository($db);
        $resolver = new StageResolver($events);
        $service = new StageTransitionService($resolver, $events, $billing, $certs, $companies);

        $companyId = $input->getInt('company_id', 0);
        $company = $companyId > 0 ? $companies->find($companyId) : null;

        if ($company === null) {
            $company = $companies->first();
        }

        if ($company === null) {
            $company = $companies->create('ACME LLC', Stage::C0);
        }

        if ($input->getMethod() === 'POST') {
            $app->checkToken() or die(Text::_('JINVALID_TOKEN'));
            $task = $input->post->getCmd('task');
            $currentStage = $resolver->resolve($company->id());

            try {
                if ($task === 'create_company') {
                    $name = trim($input->post->getString('company_name', ''));
                    if ($name === '') {
                        throw new DomainException('Введите название компании.');
                    }

                    $company = $companies->create($name, Stage::C0);
                    $app->enqueueMessage('Компания создана.', 'message');
                    $app->redirect(Route::_('index.php?option=com_stageengine&view=dashboard&company_id=' . $company->id(), false));

                    return;
                }

                $blockedAfterActivated = [
                    'attempt_contact',
                    'decision_maker_call',
                    'complete_discovery',
                    'plan_demo',
                    'complete_demo',
                    'issue_invoice',
                    'receive_payment',
                    'issue_certificate',
                ];

                if ($currentStage === Stage::A1 && in_array($task, $blockedAfterActivated, true)) {
                    throw new DomainException('Компания уже в стадии Activated. Новые pipeline-действия заблокированы.');
                }

                switch ($task) {
                    case 'attempt_contact':
                        $events->append($company->id(), 'contact_attempted', [
                            'channel' => $input->post->getString('channel', 'call'),
                        ]);
                        break;
                    case 'decision_maker_call':
                        $events->append($company->id(), 'decision_maker_call', [
                            'comment' => $input->post->getString('comment', ''),
                        ]);
                        break;
                    case 'complete_discovery':
                        $events->append($company->id(), 'discovery_completed', [
                            'notes' => $input->post->getString('discovery_notes', ''),
                        ]);
                        break;
                    case 'plan_demo':
                        $demoAt = $input->post->getString('demo_at', '');
                        if ($demoAt === '') {
                            throw new DomainException('Дата и время демо обязательны.');
                        }
                        $events->append($company->id(), 'demo_planned', ['demo_at' => $demoAt]);
                        break;
                    case 'complete_demo':
                        $events->append($company->id(), 'demo_completed', []);
                        break;
                    case 'issue_invoice':
                        if (!Stage::isAtLeast($currentStage, Stage::W3)) {
                            throw new DomainException('Счёт можно выставить только после Demo_done.');
                        }
                        $amount = (float) $input->post->getString('amount', '0');
                        if ($amount <= 0) {
                            throw new DomainException('Сумма счёта должна быть больше 0.');
                        }
                        $billing->createInvoice($company->id(), $amount);
                        $events->append($company->id(), 'invoice_issued', ['amount' => $amount]);
                        break;
                    case 'receive_payment':
                        if (!$billing->hasInvoice($company->id())) {
                            throw new DomainException('Нельзя зарегистрировать оплату без счёта.');
                        }
                        $billing->registerPaymentForLatestInvoice($company->id());
                        $events->append($company->id(), 'payment_received', []);
                        break;
                    case 'issue_certificate':
                        if (!Stage::isAtLeast($currentStage, Stage::H2)) {
                            throw new DomainException('Удостоверение выдаётся после перехода в Customer.');
                        }
                        $certs->issue($company->id());
                        $events->append($company->id(), 'certificate_issued', []);
                        break;
                    case 'transition':
                        $target = $input->post->getCmd('target_stage');
                        $service->transition($company, $target);
                        break;
                }

                $app->enqueueMessage('Операция выполнена.', 'message');
            } catch (\Throwable $e) {
                $app->enqueueMessage($e->getMessage(), 'error');
            }

            $app->redirect(Route::_('index.php?option=com_stageengine&view=dashboard&company_id=' . $company->id(), false));

            return;
        }

        $resolvedStage = $resolver->resolve($company->id());

        $this->company = (object) [
            'id' => $company->id(),
            'name' => $company->name(),
        ];
        $this->companies = array_map(
            static fn($item) => [
                'id' => $item->id(),
                'name' => $item->name(),
            ],
            $companies->all()
        );
        $this->resolvedStage = $resolvedStage;
        $this->isActivated = $resolvedStage === Stage::A1;
        $this->history = $events->history($company->id());
        $this->availableTransitions = $service->getAvailableActions($company->withStage($resolvedStage));
        $this->instructions = [
            Stage::C0 => 'Сделайте звонок ЛПР и зафиксируйте комментарий.',
            Stage::C1 => 'Заполните discovery-форму и уточните потребности.',
            Stage::C2 => 'Подтвердите интерес и переведите в Interested.',
            Stage::W1 => 'Запланируйте demo: дата и время обязательны.',
            Stage::W2 => 'Проведите demo и зарегистрируйте событие demo_completed.',
            Stage::W3 => 'Выставьте счёт, чтобы перейти к Committed.',
            Stage::H1 => 'После оплаты можно переходить в Customer.',
            Stage::H2 => 'Выдайте первое удостоверение для активации.',
            Stage::A1 => 'Компания активирована. Pipeline-действия завершены.',
        ];

        parent::display($tpl);
    }
}
