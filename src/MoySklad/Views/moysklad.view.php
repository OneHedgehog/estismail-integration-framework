<?php

?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>MoySklad Estismail</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css"
          integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
    <link rel="stylesheet" href="/integration/moysklad/сss/style.css"/>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 center">
                <div class="moysklad_login_form">
                    <div class="head">
                        <h3>Moysklad Login</h3>
                    </div>
                    <?php if ($view_data['login_alert_status'] !== 1): ?>
                        <?php if ($view_data['login_alert_status'] === 2): ?>
                            <div class="alert alert-danger" role="alert">
                                <h5>Connection failed :(</h5>
                                Please try again or <a href="https://estismail.com/" class="alert-link" target="_blank">contact
                                    to support</a>
                            </div>
                        <?php endif; ?>
                        <form action="/integration/moysklad/moysklad-login" method="POST">
                            <div class="connect-form">
                                <div class="form-group">
                                    <label for="login">Login</label>
                                    <input value="" name="login" class="form-login-field form-control" id="login"
                                           placeholder="Login"
                                           required>
                                </div>
                                <div class="form-group">
                                    <label for="password">Password:</label>
                                    <input type="password" value="" name="password"
                                           placeholder="Password"
                                           class="form-password-field form-control" id="password" required>
                                </div>
                            </div>
                            <button class="btn btn-primary estis-button">Log In</button>
                            <div class="form-row align-items-center">
                                <div class="g-recaptcha" data-sitekey="6LcXqTYUAAAAAMSMgjfeBAbfFLZalQeleKrHX1sN"></div>
                            </div>
                        </form>
                    <?php else: ?>
                        <p>
                            <b>Moysklad account:</b>
                            <span><?php echo($view_data['user_db']['login']) ?></span>
                        </p>
                        <div class="logout_form">
                            <div class="estis-menu-container">
                                <form action="" method="POST">
                                    <div class="form-group">
                                        <input type="hidden" value="true" name="logout" id="logout" required>
                                        <button type="submit" class="logout btn btn-secondary">Logout</button>
                                    </div>
                                </form>
                            </div>

                            <div class="estis-menu-container clearfix">
                                <form action="" method="POST">
                                    <div class="form-group">
                                        <input type="hidden" value="true" name="deactivate" id="deactivate" required>
                                        <button type="submit" class=" btn btn-outline-danger">Deactivate api connection</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($view_data['login_alert_status'] === 1): ?>
        <div class="row">
            <div class="col-lg-6">
                <div class="estis_api_form">
                    <div class="head">
                        <h3>Connect to Estismail</h3>
                    </div>
                    <?php if ($view_data['api_key_alert_status'] === 1): ?>
                        <p>
                            <b>Estismail account:</b>
                            <span><?php echo($view_data['estis']['user']['login']) ?></span>
                        </p>
                    <?php endif; ?>
                    <form action="/integration/moysklad/save-estis-api-key" method="POST">
                        <div class="api-connect-form">
                            <div class="form-group">
                                <label for="api_key">Api Key</label>
                                <input type="text" value="<?php echo($view_data['user_db']['api_key']) ?>"
                                       name="api_key"
                                       class="form-api-key form-control" id="api_key" required>
                            </div>
                        </div>
                        <button class="btn btn-primary estis-button">Submit</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($view_data['api_key_alert_status'] === 1): ?>
                <div class="col-lg-6">
                    <div class="estis_form_lists">
                        <div class="head">
                            <h3>Estismail Settings</h3>
                        </div>
                        <?php if (empty($view_data['user_db']['list_id'])): ?>
                            <div class="alert alert-warning" role="alert">
                                <b>Please, select the list for the counterparties subscription!</b>
                            </div>
                        <?php endif; ?>
                        <form action="/integration/moysklad/save-estis-settings" method="post">
                            <div class="form-group">
                                <div class="form-group">
                                    <label for="list">Lists</label>
                                    <?php if (isset($view_data['estis']['lists'])): ?>
                                        <select name="list" id="lists" class="form-settings form-control" required>
                                            <?php if (empty($view_data['user_db']['list_id'])): ?>
                                                <option disabled="" default="" selected="true" value="0">Choose your
                                                    list:
                                                </option>
                                            <?php endif; ?>
                                            <?php foreach ($view_data['estis']['lists'] as $list): ?>
                                                <option value="<?php echo($list['id']) ?>" <?php if ($view_data['user_db']['list_id'] == $list['id']) {
                                                    echo('selected');
                                                } ?>><?php echo($list['title']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <select name="list" id="list" class="form-settings">
                                            <option value=""></option>
                                        </select>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="form-group btn-div">
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" name="double_opt_in" value="1"
                                            <?php echo($view_data['user_db']['double_opt_in'] == 1 ? 'checked' : '') ?>>
                                        Double opt-in:
                                    </label>
                                </div>
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" name="webhook_opt_in"
                                               value="1"
                                            <?php echo($view_data['user_db']['webhook_opt_in'] == 1 ? 'checked' : '') ?>>
                                        Disable webhook:
                                    </label>
                                </div>
                                <button class="btn btn-primary estis-button">Save settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($view_data['user_db']['list_id']) && $view_data['api_key_alert_status'] === 1): ?>
                <div class="col-lg-6">
                    <div class="subscribe_all_form">
                        <div class="head">
                            <h3>Subscribe All</h3>
                        </div>
                        <form action="/integration/moysklad/subscribe-all" method="POST">
                            <div class="subscribe-all-form"></div>
                            <button class="btn btn-primary estis-button">Add all counterparties</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="subscribe_all_form">
                        <div class="head">
                            <h3>Export from Estismail list</h3>
                        </div>
                        <form action="/integration/moysklad/subscribe-list" method="POST">
                            <div class="subscribe-all-form"></div>
                            <div class="form-group">
                                <div class="form-group">
                                    <label for="list">Lists</label>
                                    <?php if (isset($view_data['estis']['lists'])): ?>
                                        <select name="list" id="lists" class="form-settings form-control" required>
                                            <?php if (empty($view_data['user_db']['list_id'])): ?>
                                                <option disabled="" default="" selected="true" value="0">Choose your
                                                    list:
                                                </option>
                                            <?php endif; ?>
                                            <?php foreach ($view_data['estis']['lists'] as $list): ?>
                                                <option value="<?php echo($list['id']) ?>" <?php if ($view_data['user_db']['list_id'] == $list['id']) {
                                                    echo('selected');
                                                } ?>><?php echo($list['title']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <select name="list" id="list" class="form-settings">
                                            <option value=""></option>
                                        </select>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button class="btn btn-primary estis-button">Export all counterparties</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
<script src='https://www.google.com/recaptcha/api.js'></script>
</html>