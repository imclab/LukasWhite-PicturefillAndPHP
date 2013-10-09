<?php
$app->get(
    '/',
    function () use ($app, $c) {
        $app->view()->setData(array(
            'title' => 'Responsive Images Example'            
        ));
        $app->render('index.html');
    }
);

$app->get(
	'/img/:parts+',
	function ($parts) use ($app, $c) {

		// Grab the filename
		$filename = array_pop($parts);

		// Grab the derivative
		$derivative = array_shift($parts);

		// Expand the paths
		$path = implode("/", $parts);

		// assemble the destination path
		$destination_path = $c['config']['images.dir'] . $derivative . '/' . $path;

		// Create the directory, if required
		if (!file_exists($destination_path)) {
			mkdir($destination_path, 0777, true); // important: set recursive to true
		}		

		// Now get the source path
		$source_path = $c['config']['images.dir'] . $path;

		// grab the config
		$config = json_decode(file_get_contents('../config/images.json'), true);

		// get the specs from the config
		$specs = $config['sizes'][$derivative];

		// Create a new Imagick object
    $image = new Imagick();

    // Ping the image
    $image->pingImage($source_path . '/' . $filename);

    // Read the image
    $image->readImage($source_path . '/' . $filename);

		// Resize, by width & height OR width OR height, depending what's configured
		$image->scaleImage(
			(isset($specs['width'])) ? $specs['width'] : 0,
			(isset($specs['height'])) ? $specs['height'] : 0
		);
			
		// save the file, for future requests
		$image->writeImage($destination_path . '/' . $filename);		

		// set the headers; first, getting the Mime type
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime_type = finfo_file($finfo, $destination_path . '/' . $filename);
		$app->response->headers->set('Content-Type', $mime_type);

		// Get the file extension, so we know how to output the file
		$path_info = pathinfo($filename);
		$ext = $path_info['extension'];

		// output the image
		echo $image;

		// Free up the image resources
		$image->destroy();

	}
);