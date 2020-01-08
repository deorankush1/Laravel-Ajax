<?php

namespace App\Http\Controllers;

ini_set('memory_limit', -1);
use Illuminate\Http\Request;
use PHPExcel\Classes\PHPExcel;

class ExcelToCSVController extends Controller
{
    public function getconvertXLStoCSV()
    {
        $inputFileName = storage_path('ProductList.xlsx');
        try {
            $inputFileType  =   \PHPExcel_IOFactory::identify($inputFileName);
            $objReader      =   \PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel    =   $objReader->load($inputFileName);
        
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();

            for ($row = 1; $row <= $highestRow; $row++) {
                if ($row == 1) {
                    $rowData = $sheet->rangeToArray(
                        'A' . $row . ':' . $highestColumn . $row,
                        null,
                        true,
                        false
                    );
                } else {
                    $data[]=$sheet->rangeToArray(
                        'A' . $row . ':' . $highestColumn . $row,
                        null,
                        true,
                        false
                    );
                    // echo implode("^", $data[][]);
                }
            }
            $CSVdata = $this->getCsvData($data, $rowData[0]);
            if ($CSVdata) {
                print_r($CSVdata);
            } else {
                echo 'something went wrong';
            }
        } catch (\Exception $e) {
            dd($e);
            die('Error loading file "'.pathinfo($inputFileName, PATHINFO_BASENAME).'": '.$e->getMessage());
        }
    }

    public function getCsvData($data, $rowData)
    {
        $csv_value =['PID','Contract Number','Product Id','MFrPN','Mfr Name','Vendor Name','Vendor PN','Cost','Coo','Shore Description','Long Description', 'UPC','UOM','Sale Start Date','Sale End Date','Sales Price'];

        $diff_values1 = array_diff($csv_value, $rowData);

        $csv_header = $success_data = $failed_data =[];
        $csv_header[] = implode("^", $csv_value);

        foreach ($data as $key => $value) {
            foreach ($value as $key1 => $value1) {
                $row = $val_data = $csvArray=[];

                if (!empty($diff_values1)) {
                    foreach ($diff_values1 as $key => $value3) {
                        $value1[] = '';
                        $rowData[]=$value3;
                    }
                }

                foreach ($value1 as $key2 => $value2) {
                    $row[]=$Data = $rowData[$key2];
                    
                    $csvArray[$Data] =$value2;
                    if (is_string($csvArray[$Data])) {
                        $csvArray[$Data] = $this->clean($csvArray[$Data]);
                    }
                    
                    if ($Data == 'Cost') {
                        if (!empty($csvArray[$Data])) {
                            if (is_numeric($csvArray[$Data])) {
                                $numberToAdd = ($csvArray[$Data] / 100) * 20;
                                $csvArray[$Data] = $csvArray[$Data] + $numberToAdd;
                            } else {
                                null;
                            }
                        }
                    }
                    if ($Data == 'COO') {
                        $csvArray[$Data] = !empty($csvArray[$Data])?$csvArray[$Data]:'TW';
                    }
                    if ($Data == 'UOM') {
                        $csvArray[$Data] = !empty($csvArray[$Data])?$csvArray[$Data]:'EA';
                    }
                    if ($Data == 'Long Description') {
                        $csvArray[$Data] = !empty($csvArray[$Data])?$csvArray[$Data]:$csvArray['Shore Description'];
                    }
                    if ($Data == 'Contract Number') {
                        $csvArray[$Data] = !empty($csvArray[$Data])?$csvArray[$Data]:'XXXX';
                    }
                    $val_data[$Data] = $this->validateData($csvArray, $Data);
                }

                $csv_format_data = $this->insert_csv_data($csv_value, $csvArray);
                
                if (in_array(false, $val_data)) {
                    $csvArray = implode("^", $csv_format_data);
                    $failed_data[] = $csvArray;
                } else {
                    $csvArray = implode("^", $csv_format_data);
                    $success_data[] = $csvArray;
                }
            }
        }
        $this->insertIntoCSV($success_data, $csv_header);
        $this->insertIntoExcel($failed_data, $csv_header);
        $success_count = count($success_data);
        $failed_count = count($failed_data);
        $data_count =[];
        $data_count['success_count'] = $success_count;
        $data_count['failed_count'] = $failed_count;
        return $data_count;
    }

    public function insert_csv_data($csv_value, $csvArray)
    {
        $data =[];
        foreach ($csv_value as $key => $value) {
            $data[$value] = $csvArray[$value];
        }
        return $data;
    }

    public function validateData($csvArray, $data)
    {
        switch ($data) {
            case "PID":
                if (empty($csvArray[$data])) {
                    return false;
                }
                if (strlen($csvArray[$data]) < 21 && is_numeric($csvArray[$data])) {
                    // $csvArray['Contract'] = 'XXXX'; //we can use substr() for remove the data;
                    return true;
                } else {
                    return false;
                }
                break;
            case "Product Id":
            case "Mfr Name":
            case "Vendor Name":
            case "MFrPN":
            case "Vendor PN":
                if (empty($csvArray[$data])) {
                    return false;
                }
                 if (strlen($csvArray[$data]) < 51) {
                     return true;
                 } else {
                     //echo "in vendor";
                     return false;
                 }
                 // no break
            case "Cost":
                if (!empty($csvArray[$data])) {
                    return true;
                } else {
                    //echo "in Cost";
                    return false;
                }
                break;
            case "Coo":
            case "UOM":
                if (empty($csvArray[$data])) {
                    return false;
                }
                if (strlen($csvArray[$data]) < 3) {
                    return true;
                } else {
                    //echo "in Coo";
                    return false;
                }
                break;
            case "Shore Description":
                if (empty($csvArray[$data])) {
                    return false;
                }
                if (strlen($csvArray[$data]) < 301) {
                    return true;
                } else {
                    echo "in shoee";
                    return false;
                }
                break;
            default:
                return true;
                break;
        }
    }

    public function insertIntoCSV($success_data, $csv_header)
    {
        $insert_data =  array_chunk($success_data, 10000);
        $i=0;
        foreach ($insert_data as $key => $value) {
            $i++;
            $this->outputCsv('Product'.$i.'.csv', $value, $csv_header);
        }
        return true;
    }

    public function insertIntoExcel($success_data, $csv_header)
    {
        $insert_data =  array_chunk($success_data, 10000);
        $i=0;
        foreach ($insert_data as $key => $value) {
            $i++;
            $this->outputCsv('Error'.$i.'.csv', $value, $csv_header);
        }
        return true;
    }

    public function outputCsv($fileName, $assocDataArray, $csv_header)
    {
        if (isset($assocDataArray['0'])) {
            $fp = fopen($fileName, 'w+');
            fputcsv($fp, $csv_header);
            fputcsv($fp, $assocDataArray, ",");
            fclose($fp);
        }
    }

    public function clean($string)
    {
        return $string = str_replace('^', '-', $string);
    }
}
