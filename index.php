<?php

include_once './config.php';

function searchFolder($folderName, $pathToFolder = ROOT_DIRECTORY)
{
    global $stopSearchFolderFlag;
    global $desiredFolder;

    if (!$stopSearchFolderFlag) {
        $folderContents = scandir($pathToFolder);
        foreach ($folderContents as $folder) {
            if (
                $folder !== '.' &&
                $folder !== '..' &&
                is_dir($pathToFolder . $folder) && 
                $folder === $folderName
            ) {
                $stopSearchFolderFlag = true;
                $desiredFolder = $pathToFolder . $folder;
            } elseif (
                $folder !== '.' &&
                $folder !== '..' &&
                is_dir($pathToFolder . $folder) && 
                $folder !== $folderName
            ) {
                searchFolder($folderName, $pathToFolder . $folder . '/');
            }
        }
    }
}

function getNamesOfFilesInFolderBySubtring($folderName, $substring)
{
    global $stopSearchFolderFlag;
    global $desiredFolder;

    $filesNames = [];

    $stopSearchFolderFlag = false;
    $desiredFolder = null;
    searchFolder($folderName);

    if (isset($desiredFolder)) {
        $folderContent = scandir($desiredFolder);

        foreach ($folderContent as $file) {
            if (is_file($desiredFolder . '/' . $file) && strpos($file, $substring) !== false) {
                $filesNames[] = $file;
            }
        }
    } else {
        echo "папка '$folderName' не найдена<br>";
    }
    
    return $filesNames;
}

function extractDateFromFileName($fileName)
{
    foreach (DICTIONARY_FOR_DATE_TRANSLATION as $key => $value) {
        if (strpos($fileName, $key) !== false) {
            foreach ($value['словарь'] as $key => $value) {
                if (preg_match('/\b(\d{4})'. $value . '_/', $fileName, $matches)) {
                    return $matches[1] . '.' . $key;
                }
            }
        }
    }
}

function getDatesToCheck($beginYear, $beginMonth, $substring)
{
    $datesToCheck = [];
    for (
        $i = $beginYear, $firstYearFlag = true;
        $i <= date('Y');
        $i++, $firstYearFlag = false
    ) {
        $months = array_keys(DICTIONARY_FOR_DATE_TRANSLATION[$substring]['словарь']);
        for (
            $j = 0, $beginMonthFlag = false, $endMonthFlag = false, $nextEndMonthFlag = false;
            $j < count($months);
            $j++
        ) {
            $beginMonthFlag = !$beginMonthFlag ? checkIfThereIsMonthInLine($months[$j], $beginMonth) : true;
            $nextEndMonthFlag = $endMonthFlag ? true : false;
            $endMonthFlag = !$endMonthFlag ? checkIfThereIsMonthInLine($months[$j], date('m')) : true;
            if ($firstYearFlag && $beginMonthFlag || !$firstYearFlag && $i != date('Y') || $i == date('Y') && !$nextEndMonthFlag) {
                $datesToCheck[] = $i . '.' . $months[$j];
            }
        }
    }
    return $datesToCheck;
}

function checkIfThereIsMonthInLine($line, $month)
{
    return (strpos($line, $month) !== false) ? true : false;
}

function getDatesFromFilesNames($folderName, $substring)
{
    $datesFromFileName = [];
    $filesNames = getNamesOfFilesInFolderBySubtring($folderName, $substring);
    foreach ($filesNames as $fileName) {
        $datesFromFileName[] = extractDateFromFileName($fileName);
    }

    return $datesFromFileName;
}

function identifyMissingDatesInFolder($beginYear, $beginMonth, $folderName, $substring)
{
    $datesToCheck = getDatesToCheck($beginYear, $beginMonth, $substring);
    $datesFromFileName = getDatesFromFilesNames($folderName, $substring);
    $missingDates = [];

    foreach ($datesToCheck as $dateToCheck) {
        $flag = false;
        foreach($datesFromFileName as $dateFromFileName) {
            if ($dateToCheck === $dateFromFileName) {
                $flag = true;
                break;
            }
        }
        if (!$flag) {
            $missingDates[] = $dateToCheck;
        }
    }

    return $missingDates;
}

function identifyAllMissingDates($dictionary)
{
    foreach ($dictionary as $fileName => $value) {
        $missingDatesInFolder = identifyMissingDatesInFolder($value['начальный год'], $value['начальный месяц'], $value['папка'], $fileName);
        echo "<p><b>$fileName:</b></p>";
        echo '<ul>';
        foreach ($missingDatesInFolder as $missingDate) {
            echo "<li>$missingDate</li>";
        }
        echo '</ul>';
    }
}

if (file_exists('./config.php')) {
    identifyAllMissingDates(DICTIONARY_FOR_DATE_TRANSLATION);
} else {
    echo 'config.php отсутствует';
}
