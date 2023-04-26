<?php
/**
 * ShowController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Account;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use FireflyIII\Support\Http\Controllers\AugumentData;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use JsonException;
use Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    use PeriodOverview;
    use AugumentData;
    private AccountRepositoryInterface $repository;

    /**
     * ShowController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        app('view')->share('showCategory', true);

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-credit-card');
                app('view')->share('title', (string)trans('firefly.accounts'));

                $this->repository = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show an account.
     *
     * @return Factory|Redirector|RedirectResponse|View
     *
     * @throws FireflyException
     *                                              */
    public function show(Request $request, Account $account, ?Carbon $start = null, ?Carbon $end = null)
    {
        $objectType       = config(sprintf('firefly.shortNamesByFullName.%s', $account->accountType->type));

        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account);
        }

        // @var Carbon $start
        $start ??= session('start');
        // @var Carbon $end
        $end   ??= session('end');

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        $location         = $this->repository->getLocation($account);
        $attachments      = $this->repository->getAttachments($account);
        $today            = today(config('app.timezone'));
        $subTitleIcon     = config(sprintf('firefly.subIconsByIdentifier.%s', $account->accountType->type));
        $page             = (int)$request->get('page');
        $pageSize         = (int)app('preferences')->get('listPageSize', 50)->data;
        $currency         = $this->repository->getAccountCurrency($account) ?? app('amount')->getDefaultCurrency();
        $fStart           = $start->isoFormat($this->monthAndDayFormat);
        $fEnd             = $end->isoFormat($this->monthAndDayFormat);
        $subTitle         = (string)trans('firefly.journals_in_period_for_account', ['name' => $account->name, 'start' => $fStart, 'end' => $fEnd]);
        $chartUrl         = route('chart.account.period', [$account->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $firstTransaction = $this->repository->oldestJournalDate($account) ?? $start;
        $periods          = $this->getAccountPeriodOverview($account, $firstTransaction, $end);

        // if layout = v2, overrule the page title.
        if ('v1' !== config('view.layout')) {
            $subTitle = (string)trans('firefly.all_journals_for_account', ['name' => $account->name]);
        }

        /** @var GroupCollectorInterface $collector */
        $collector        = app(GroupCollectorInterface::class);
        $collector
            ->setAccounts(new Collection([$account]))
            ->setLimit($pageSize)
            ->setPage($page)->withAccountInformation()->withCategoryInformation()
            ->setRange($start, $end);
        $categorySummaryData = $this->getCategorySummaryData($collector);
        $groups = $collector->getPaginatedGroups();

        $groups->setPath(route('accounts.show', [$account->id, $start->format('Y-m-d'), $end->format('Y-m-d')]));
        $showAll          = false;
        $balance          = app('steam')->balance($account, $end);

        return view(
            'accounts.show',
            compact(
                'account',
                'showAll',
                'objectType',
                'currency',
                'today',
                'periods',
                'subTitleIcon',
                'groups',
                'attachments',
                'subTitle',
                'start',
                'end',
                'chartUrl',
                'location',
                'balance',
                'categorySummaryData'
            )
        );
    }

    /**
     * Show an account.
     *
     * @return Factory|Redirector|RedirectResponse|View
     *
     * @throws FireflyException
     *                                              */
    public function showAll(Request $request, Account $account)
    {
        Log::debug('Now in API ShowController::showAll()');
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account);
        }
        $location     = $this->repository->getLocation($account);
        $isLiability  = $this->repository->isLiability($account);
        $attachments  = $this->repository->getAttachments($account);
        $objectType   = config(sprintf('firefly.shortNamesByFullName.%s', $account->accountType->type));
        $end          = today(config('app.timezone'));
        $today        = today(config('app.timezone'));
        $start        = $this->repository->oldestJournalDate($account) ?? today(config('app.timezone'))->startOfMonth();
        $subTitleIcon = config('firefly.subIconsByIdentifier.'.$account->accountType->type);
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $currency     = $this->repository->getAccountCurrency($account) ?? app('amount')->getDefaultCurrency();
        $subTitle     = (string)trans('firefly.all_journals_for_account', ['name' => $account->name]);
        $periods      = new Collection();

        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setLimit($pageSize)->setPage($page)->withAccountInformation()->withCategoryInformation();
        $categorySummaryData = $this->getCategorySummaryData($collector);
        // print_r ($categorySummaryData);
        $groups       = $collector->getPaginatedGroups();
        $groups->setPath(route('accounts.show.all', [$account->id]));
        $chartUrl     = route('chart.account.period', [$account->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $showAll      = true;
        $balance      = app('steam')->balance($account, $end);

        return view(
            'accounts.show',
            compact(
                'account',
                'showAll',
                'location',
                'objectType',
                'isLiability',
                'attachments',
                'currency',
                'today',
                'chartUrl',
                'periods',
                'subTitleIcon',
                'groups',
                'subTitle',
                'start',
                'end',
                'balance',
                'categorySummaryData'
            )
        );
    }
    public function getCategorySummaryData(GroupCollectorInterface $collector){
        Log::debug('Now in API ShowController::getCategorySummaryData()');
        $journals  = $collector->getExtractedJournals();
        $result    = [];
        $chartData = [];

        /** @var array $journal */
        foreach ($journals as $journal) {
            $key = sprintf('%d-%d', $journal['category_id'], $journal['currency_id']);
            if (!array_key_exists($key, $result)) {
                $result[$key] = [
                    'total'           => '0',
                    'category_id'     => (int)$journal['category_id'],
                    'currency_name'   => $journal['currency_name'],
                    'currency_symbol' => $journal['currency_symbol'],
                    'currency_code'   => $journal['currency_code'],
                ];
            }
            $result[$key]['total'] = bcadd($journal['amount'], $result[$key]['total']);
        }
        $names = $this->getCategoryNames(array_keys($result));
        foreach ($result as $row) {
            $categoryId        = $row['category_id'];
            $name              = $names[$categoryId] ?? '(unknown)';
            // $label             = (string)trans('firefly.name_in_currency', ['name' => $name, 'currency' => $row['currency_name']]);
            $chartData[$name] = ['amount' => $row['total']];
        }
        return $chartData;
    }
}
