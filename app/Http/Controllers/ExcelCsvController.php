<?php

namespace App\Http\Controllers;

ini_set('memory_limit', -1);
use Illuminate\Http\Request;
use PHPExcel\Classes\PHPExcel.php;

class ExcelCsvController extends Controller
{
    public function getconvertXLStoCSV($infile,$outfile)
    {
    	$fileType = PHPExcel_IOFactory::identify($infile);
 	$objReader = PHPExcel_IOFactory::createReader($fileType);
 
    	$objReader->setReadDataOnly(true);   
    	$objPHPExcel = $objReader->load($infile);    
 
    	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
    	$objWriter->save($outfile);
    }
    

}
