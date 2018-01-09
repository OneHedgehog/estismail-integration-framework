<?php
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>InSales estismail :)</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css"
          integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
    <link rel="stylesheet" href="/integration/insales/css/main.css">
</head>
<body>
<video loop muted autoplay poster="/integration/insales/img/I_Just_Wanted.jpg"
       class="fullscreen-bg__video">
    <source src="/integration/insales/img/I_Just_Wanted.mp4" type="video/mp4">
    <source src="/integration/insales/img/I_Just_Wanted.webm" type="video/webm">
</video>
<div class="container">
    <div class="row">
        <div class="col-lg-6 center">
            <div class="InSales">
                <p>
                    <b>Insales Account:
                    </b>
                    <span>
                    <?php echo( $view_data['db']['shop'] ) ?>
                    </span>
                </p>
                <p>
                    <b>Insales Email:
                    </b>
                    <span>
                    <?php echo( $view_data['db']['user_email'] ) ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 center">
            <div class="estis_form">
                <div class="head">
                    <h3>Estismail InSales app</h3>
                </div>
                <div class="alert alert-<?php echo( $view_data['alert_data'][ $view_data['alert_status'] ]['class'] ) ?> estis-control"
                     role="alert">
                    <strong><?php echo( $view_data['alert_data'][ $view_data['alert_status'] ]['mes'] ) ?></strong>
                </div>
                <form action="/integration/insales/save-api-key/" method="POST">
                    <div class="form-group">
                        <label for="api_key">Api key</label>
                        <input type="text" value="<?php echo( $view_data['db']['api_key'] ) ?>" name="api_key"
                               class="form-control" id="api_key" required>
                        <input type="hidden" value="<?php echo( $view_data['db']['id'] ) ?>" name="id"/>
                        <input type="hidden" value="<?php echo( sha1( 'RYqxUsnCkpQpQMyfv23vopsBBE72aRV6LQ0quLDI' . $view_data['db']['id'] ) ) ?>"
                               name="id_hash"/>
                    </div>
                    <button class="btn btn-outline-success">Submit</button>
                </form>
            </div>
        </div>
    </div>

	<?php if ( $view_data['alert_status'] === 1 ): ?>
        <!-- Modal -->
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <form method="POST"
                  action="/integration/insales/subscribe-all-clients/">
                <input type="hidden" value="<?php echo( $view_data['db']['id'] ) ?>" name="id">
                <input type="hidden" value="<?php echo( sha1('hp9Ur8Rvw0tTTC7WbklFkUKYqKzH7bxL072V8Wru' . $view_data['db']['id'] )) ?>" name="hash_id">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Warning</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            Adding all clients is a long process. Are u sure, that you want to do it?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-outline-success allEmails">Get All</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="row">
            <div class="col-lg-6 center">
                <div class="estis_form_lists">

                    <div class="head">
                        <h3>Estis Settings</h3>
                    </div>

					<?php if ( empty( $view_data['db']['list_id'] ) ): ?>
                        <div class="alert alert-warning" role="alert">
                            <b>Please, update your settings !</b>
                        </div>
					<?php endif; ?>
                    <form action="/integration/insales/save-estis-settings/"
                          method="post">
                        <input type="hidden" value="<?php echo( $view_data['db']['id'] ) ?>" name="id">
                        <input type="hidden" value="<?php echo( sha1( 'x9KRMg2rmitUTkviVQHxoOuD0PmTOmKatNWPJ7gm' . $view_data['db']['id'] ) ) ?>"
                               name="id_hash"/>
                        <div class="form-group">
                            <label for="list">Lists</label>
							<?php if ( isset( $view_data['estis']['lists'] ) ): ?>
                                <select name="list" id="lists" class="form-control" required>
									<?php if ( empty( $view_data['db']['list_id'] ) ): ?>
                                        <option disabled="" default="" selected="true" value="0">Choose your list:
                                        </option>
									<?php endif; ?>
									<?php foreach ( $view_data['estis']['lists'] as $list ): ?>
                                        <option value="<?php echo( $list['id'] ) ?>" <?php if ( $view_data['db']['list_id'] == $list['id'] ) {
											echo( 'selected' );
										} ?>><?php echo( $list['title'] ) ?></option>
									<?php endforeach; ?>
                                </select>
							<?php else: ?>
                                <select name="list" id="list" class="form-control">
                                    <option value=""></option>
                                </select>
							<?php endif; ?>
                        </div>
                        <div class="form-group btn-div">
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input"
                                           name="double_opt_in"
                                           value="1"
										<?php echo( $view_data['db']['double_opt_in'] == 1 ? 'checked' : '' ) ?>>
                                    Double opt-in
                                </label>
                            </div>
                            <button class="btn btn-outline-success">Save settings</button>
                        </div>
	                    <?php if ( ! empty( $view_data['db']['last_subscribe_email'] ) ): ?>
                            <p>
                                <b>Last order subscribe:</b>
                                <span>
                                <?php if ( ( $view_data['db']['last_subscribe_email']['valid'] ) === false ): ?>
                                    Subscription error in order id: <?php echo( $view_data['db']['last_subscribe_email']['order_id'] ) ?>
                                <?php elseif ( ( $view_data['db']['last_subscribe_email']['valid'] ) === true): ?>
	                                <?php echo( $view_data['db']['last_subscribe_email']['email'] ) ?>
                                <?php endif; ?>
                            </span>
                            </p>

	                    <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 center">
                <div class="subscribe_all_form">
                    <div class="head">
                        <h3>Subscribe All</h3>
                    </div>
                    <button type="button" class="btn btn-outline-success" data-toggle="modal"
                            data-target="#exampleModal">
                        Add all clients
                    </button>
	                <?php if ( ! empty( $view_data['db']['err_proc']['success'] ) ): ?>
                        <ul class="list-group estis_subscribe_all_listing">
                            <li class="col-lg-6 list-group-item">
                                Succefully subscribed:
                                <b><?php echo( $view_data['db']['err_proc']['success'] ) ?></b>
                            </li>
                            <li class="col-lg-6 list-group-item">
                                Subscription errors:
                                <b><?php echo( $view_data['db']['err_proc']['err'] ) ?></b>
                            </li>
                        </ul>

	                <?php endif; ?>
                </div>
            </div>
        </div>

	<?php endif; ?>

</div>


<!--modal for api_key value control-->
<div class="modal" id="api-key-value-control">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Api key validation</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Api key should be less then 40 symbols</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-success" data-dismiss="modal">Ok</button>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
        integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
        crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"
        integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4"
        crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js"
        integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1"
        crossorigin="anonymous"></script>
<script src="/integration/insales/js/login.js"></script>
</body>
</html>
