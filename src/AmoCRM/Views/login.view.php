<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 11/9/17
 * Time: 12:19 PM
 */
//print_r($view_data);
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
	      content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
    <link rel="stylesheet" href="/integration/amoCRM/css/style.css">
	<title>Document</title>
</head>
<body>
<div class="container">
	<div class="row">
		<div class="col-md-7 center">
			<div class="estis-contanier">
                <h4>Estismail connection</h4>
                <div class="alert <?php echo($view_data['alert_data'][$view_data['alert_status']]['class']) ?>" role="alert">
                    <b> <?php echo($view_data['alert_data'][$view_data['alert_status']]['mes']) ?></b>
                </div>
				<form method="POST" action="/integration/amoCRM/save-api-key">
					<div class="form-group">
						<label for="exampleInputEmail1">Api key</label>
						<input type="text" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter estis api-key" name="api_key" value="<?php echo($view_data['db']['api_key']) ?>" required>
                        <input type="hidden" value="<?php echo($view_data['db']['id']) ?>" name="id">
                        <input type="hidden" value="<?php echo(sha1($view_data['db']['id'] . 'q2oRxAb41acKskblNzCBcQ4U1RS72tjgbwUZeh6b')) ?>" name="hash">
						<small id="emailHelp" class="form-text text-muted">We'll never share your api key with anyone else.</small>
					</div>
                    <button type="submit" class="btn btn-primary">Submit</button>
				</form>
			</div>
		</div>
	</div>
</div>

</body>
</html>
