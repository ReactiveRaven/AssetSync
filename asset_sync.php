<?php

//chdir(__DIR__);

$settings = json_decode(file_get_contents("asset_sync.json"), true);
$settings = $settings["asset_sync"];
$sources = isset($settings["sources"]) ? $settings["sources"] : array();
$settings = $settings["settings"];

$sourceNames = array_keys($sources);

if (count($sourceNames) < 1) {
  echo "\n  [!!] No sources defined\n";
  echo   "       Define a source in asset_sync.json[\"asset_sync\"][\"sources\"][\$source_name] like so:\n";
  echo   "       {\n";
  echo   "         ...\n";
  echo   "         \"asset_sync\": {\n";
  echo   "           \"sources\": {\n";
  echo   "             \"build\": {\n";
  echo   "               \"local_folder_or_file\": \"user@host:/path/to/remote/folder/or/file\",\n";
  echo   "               ...\n";
  echo   "             }\n";
  echo   "           }\n";
  echo   "         }\n";
  echo   "         ...\n";
  echo   "       }\n";
  echo   "       Note that if you want to pull an entire folder into an existing local folder,\n";
  echo   "       include the trailing slash on the remote folder.\n\n";
  echo   "       Asset Sync currently only supports rsync connections.\n\n";
  die();
}

$sourceName = (isset($settings["default_source"]) ? $settings["default_source"] : $sourceNames[0]);
$settings["default_source"] = $sourceName;
$source = $sources[$sourceName];

if (count($sourceNames) > 1) {

  $response = null;
  while ($response === null) {
    echo "\n\n";
    $defaultIndex = 0;
    foreach ($sourceNames as $key => $val) {
      echo " [" . ($key + 1) . "] " . $val . "\n";
      if ($val == $settings["default_source"]);
    }
    echo "Please choose a source to use: [" . ($defaultIndex + 1) . "]: ";
    $response = readline();
    if (strlen($response) == 0) {
      $response = $defaultIndex + 1;
    }
    if (!is_numeric($response) || $response >= count($sourceNames) + 1 || $response < 1) {
      echo "\n\nPLEASE CHOOSE BE A NUMBER\n\n";
      $response = null;
    }
  }

  $source = $sources[$sourceNames[((int)$response) - 1]];
  $sourceName = $sourceNames[((int)$response) - 1];
}

echo "\n\n  [  ] Pulling uploads...\n";

chdir($settings["project_root"]);

$totalFolders = count($source);
$currentFolder = 0;

foreach ($source as $folder => $rsync_source) {
  $result = null;
  $output = array();
  $output = $output;

  $percent = floor($currentFolder++ / $totalFolders * 100);

  $currentFolderString = $currentFolder;
  while (strlen($currentFolderString) < strlen($totalFolders)) {
    $currentFolderString = "0" . $currentFolderString;
  }

  echo "  [" . ($percent < 10 ? "0" : "") . $percent . "] " . $currentFolderString . "/" . $totalFolders . " Pulling from " . $sourceName . " into \"" . $folder . "\"... ";

  $excludes = "";
  if (isset($settings["exclude"])) {
    foreach ($settings["exclude"] as $excl) {
      $excludes .= "--exclude=\"" . $excl . "\" ";
    }
  }

  $cmd = "rsync -avz --delete " . $excludes . "\"" . $rsync_source . "\" \"" . $folder . "\"";

  exec($cmd, $output, $result);

  if ($result) {
    echo $output;
    die("\n\n  [!!] Rsync threw an error on directory '" . $folder . "' when pulling from '" . $rsync_source . "'\n\n");
  }
  else
  {
    echo "OK\n";
  }
}

echo "  [OK] Pulled uploads OK\n\n";
