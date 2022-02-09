<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* {
  font-family: 'Arial', 'Gill Sans', 'Gill Sans MT',
  ' Calibri', 'Trebuchet MS', 'sans-serif';
  box-sizing: border-box;

	box-sizing: border-box;
}

/* Create two unequal columns that floats next to each other */
.column {
	float: left;
	padding: 10px;
}

.first {
	width: calc(50% - 70px);
}

.second {
	width: calc(50% - 30px);
}

/* Clear floats after the columns */
.row:after {
	content: "";
	display: table;
	clear: both;
}
body {
	background-color: rgb(119, 196, 135);
}

a {
	text-decoration: none;
	color: white;
}

.block {
	display: block;
	font-weight: bold;
	width:100%;
	border: none;
	background-color: #04AA6D;
	padding: 20px 20px;
	color: white;
	font-size: medium;
	cursor: pointer;
	text-align: center;
}
		@media screen and (max-width: 800px) {
			.column {
				float: none;
				width: 100%;
			}
		}
</style>
<body style="background-color: rgb(119, 196, 135);">
	<div class="row">
		<div class="column first">
			<form action="/scripts/stop_core_services.php" onclick="return confirm('Stop core services?')">
				<button type="submit" class="block">Stop Core Services</button>
			</form>
			<form action="/scripts/restart_services.php" onclick="return confirm('Restart ALL services?')">
				<button type="submit" class="block">Restart ALL Services</button>
			</form>
			<form action="/scripts/restart_birdnet_analysis.php">
				<button type="submit" class="block">Restart BirdNET Analysis</button>
			</form>
			<form action="/scripts/restart_birdnet_recording.php">
				<button type="submit" class="block">Restart Recording</button>
			</form>
			<form action="/scripts/restart_extraction.php">
				<button type="submit" class="block">Restart Extraction</button>
			</form>
			<form action="/scripts/restart_caddy.php" onclick="return confirm('Restart Caddy? You will be disconnected for about 20 seconds.')">
				<button type="submit" class="block">Restart Caddy</button>
			</form>
		</div>
	</div>
</body>
