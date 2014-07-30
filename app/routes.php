<?php

// models:
Route::bind('account', function($value, $route)
    {
        if(Auth::check()) {
            return Account::
                where('id', $value)->
                where('user_id',Auth::user()->id)->first();
        }
        return null;
    });
Route::bind('budget', function($value, $route)
    {
        if(Auth::check()) {
            return Budget::
                where('id', $value)->
                where('user_id',Auth::user()->id)->first();
        }
        return null;
    });

Route::bind('category', function($value, $route)
{
    if(Auth::check()) {
        return Category::
            where('id', $value)->
            where('user_id',Auth::user()->id)->first();
    }
    return null;
});

Route::bind('limit', function($value, $route)
    {
        if(Auth::check()) {
            return Limit::
                where('limits.id', $value)->
                leftJoin('components','components.id','=','limits.component_id')->
                where('components.class','Budget')->
                where('components.user_id',Auth::user()->id)->first();
        }
        return null;
    });


// protected routes:
Route::group(['before' => 'auth'], function () {

        // home controller
        Route::get('/', ['uses' => 'HomeController@index', 'as' => 'index']);
        Route::get('/flush', ['uses' => 'HomeController@flush', 'as' => 'flush']);

        // chart controller
        Route::get('/chart/home/account/{account?}', ['uses' => 'ChartController@homeAccount', 'as' => 'chart.home']);
        Route::get('/chart/home/categories', ['uses' => 'ChartController@homeCategories', 'as' => 'chart.categories']);
        Route::get('/chart/home/budgets', ['uses' => 'ChartController@homeBudgets', 'as' => 'chart.budgets']);
        Route::get('/chart/home/info/{accountname}/{day}/{month}/{year}', ['uses' => 'ChartController@homeAccountInfo', 'as' => 'chart.info']);
        Route::get('/chart/categories/show/{category}', ['uses' => 'ChartController@categoryShowChart','as' => 'chart.showcategory']);

        // Categories controller:
        Route::get('/categories',['uses' => 'CategoryController@index','as' => 'categories.index']);
        Route::get('/categories/create',['uses' => 'CategoryController@create','as' => 'categories.create']);
        Route::get('/categories/show/{category}',['uses' => 'CategoryController@show','as' => 'categories.show']);
        Route::get('/categories/edit/{category}',['uses' => 'CategoryController@edit','as' => 'categories.edit']);
        Route::get('/categories/delete/{category}',['uses' => 'CategoryController@delete','as' => 'categories.delete']);


        // preferences controller
        Route::get('/preferences', ['uses' => 'PreferencesController@index', 'as' => 'preferences']);

        // user controller
        Route::get('/logout', ['uses' => 'UserController@logout', 'as' => 'logout']);

        //profile controller
        Route::get('/profile', ['uses' => 'ProfileController@index', 'as' => 'profile']);
        Route::get('/profile/change-password',['uses' => 'ProfileController@changePassword', 'as' => 'change-password']);

        // account controller:
        Route::get('/accounts', ['uses' => 'AccountController@index', 'as' => 'accounts.index']);
        Route::get('/accounts/create', ['uses' => 'AccountController@create', 'as' => 'accounts.create']);
        Route::get('/accounts/{account}', ['uses' => 'AccountController@show', 'as' => 'accounts.show']);
        Route::get('/accounts/{account}/edit', ['uses' => 'AccountController@edit', 'as' => 'accounts.edit']);
        Route::get('/accounts/{account}/delete', ['uses' => 'AccountController@delete', 'as' => 'accounts.delete']);

        // budget controller:
        Route::get('/budgets',['uses' => 'BudgetController@indexByDate','as' => 'budgets.index']);
        Route::get('/budgets/create',['uses' => 'BudgetController@create', 'as' => 'budgets.create']);
        Route::get('/budgets/budget',['uses' => 'BudgetController@indexByBudget','as' => 'budgets.index.budget']);
        Route::get('/budgets/show/{budget}',['uses' => 'BudgetController@show', 'as' => 'budgets.show']);
        Route::get('/budgets/edit/{budget}',['uses' => 'BudgetController@edit', 'as' => 'budgets.edit']);
        Route::get('/budgets/delete/{budget}',['uses' => 'BudgetController@delete', 'as' => 'budgets.delete']);

        // limit controller:
        Route::get('/budgets/limits/create/{budget?}',['uses' => 'LimitController@create','as' => 'budgets.limits.create']);
        Route::get('/budgets/limits/delete/{limit}',['uses' => 'LimitController@delete','as' => 'budgets.limits.delete']);
        Route::get('/budgets/limits/edit/{limit}',['uses' => 'LimitController@edit','as' => 'budgets.limits.edit']);

        // JSON controller:
        Route::get('/json/beneficiaries', ['uses' => 'JsonController@beneficiaries', 'as' => 'json.beneficiaries']);
        Route::get('/json/categories', ['uses' => 'JsonController@categories', 'as' => 'json.categories']);


        // transaction controller:
        Route::get('/transactions/create/{what}', ['uses' => 'TransactionController@create', 'as' => 'transactions.create'])->where(['what' => 'withdrawal|deposit|transfer']);
        Route::get('/transaction/show/{id}',['uses' => 'TransactionController@show','as' => 'transactions.show']);
        Route::get('/transaction/edit/{id}',['uses' => 'TransactionController@edit','as' => 'transactions.edit']);
        Route::get('/transaction/delete/{id}',['uses' => 'TransactionController@delete','as' => 'transactions.delete']);
        Route::get('/transactions/index',['uses' => 'TransactionController@index','as' => 'transactions.index']);
        // migration controller
        Route::get('/migrate', ['uses' => 'MigrationController@index', 'as' => 'migrate']);

    }
);

// protected + csrf routes (POST)
Route::group(['before' => 'csrf|auth'], function () {
        // profile controller
        Route::post('/profile/change-password', ['uses' => 'ProfileController@postChangePassword']);

        // budget controller:
        Route::post('/budgets/store/{budget}',['uses' => 'BudgetController@store', 'as' => 'budgets.store']);
        Route::post('/budgets/update', ['uses' => 'BudgetController@update', 'as' => 'budgets.update']);
        Route::post('/budgets/destroy', ['uses' => 'BudgetController@destroy', 'as' => 'budgets.destroy']);

        // category controller
        Route::post('/categories/store',['uses' => 'CategoryController@store', 'as' => 'categories.store']);
        Route::post('/categories/update', ['uses' => 'CategoryController@update', 'as' => 'categories.update']);
        Route::post('/categories/destroy', ['uses' => 'CategoryController@destroy', 'as' => 'categories.destroy']);

        // migration controller
        Route::post('/migrate', ['uses' => 'MigrationController@postIndex']);

        // preferences controller
        Route::post('/preferences', ['uses' => 'PreferencesController@postIndex']);

        // account controller:
        Route::post('/accounts/store', ['uses' => 'AccountController@store', 'as' => 'accounts.store']);
        Route::post('/accounts/update', ['uses' => 'AccountController@update', 'as' => 'accounts.update']);
        Route::post('/accounts/destroy', ['uses' => 'AccountController@destroy', 'as' => 'accounts.destroy']);

        // limit controller:
        Route::post('/budgets/limits/store/{budget?}', ['uses' => 'LimitController@store', 'as' => 'budgets.limits.store']);
        Route::post('/budgets/limits/destroy/{id?}',['uses' => 'LimitController@destroy','as' => 'budgets.limits.destroy']);
        Route::post('/budgets/limits/update/{id?}',['uses' => 'LimitController@update','as' => 'budgets.limits.update']);

        // transaction controller:
        Route::post('/transactions/store/{what}', ['uses' => 'TransactionController@store', 'as' => 'transactions.store'])
            ->where(['what' => 'withdrawal|deposit|transfer']);
        Route::post('/transaction/update/{id}',['uses' => 'TransactionController@update','as' => 'transactions.update']);

    }
);

// guest routes:
Route::group(['before' => 'guest'], function () {
        // user controller
        Route::get('/login', ['uses' => 'UserController@login', 'as' => 'login']);
        Route::get('/register', ['uses' => 'UserController@register', 'as' => 'register']);
        Route::get('/verify/{verification}', ['uses' => 'UserController@verify', 'as' => 'verify']);
        Route::get('/reset/{reset}', ['uses' => 'UserController@reset', 'as' => 'reset']);
        Route::get('/remindme', ['uses' => 'UserController@remindme', 'as' => 'remindme']);

        // dev import route:
        Route::get('/dev',['uses' => 'MigrationController@dev']);
        Route::get('/limit',['uses' => 'MigrationController@limit']);
    }
);

// guest + csrf routes:
Route::group(['before' => 'csrf|guest'], function () {

        // user controller
        Route::post('/login', ['uses' => 'UserController@postLogin']);
        Route::post('/register', ['uses' => 'UserController@postRegister']);
        Route::post('/remindme', ['uses' => 'UserController@postRemindme']);
    }
);