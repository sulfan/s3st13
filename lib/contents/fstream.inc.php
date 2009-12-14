<?php
/**
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com), Hendro Wicaksono (hendrowicaksono@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/* File Viewer */

session_start();

// get file ID
$fileID = isset($_GET['fid'])?(integer)$_GET['fid']:0;
// get biblioID
$biblioID = isset($_GET['bid'])?(integer)$_GET['bid']:0;

// query file to database
$sql_q = 'SELECT att.*, f.* FROM biblio_attachment AS att
    LEFT JOIN files AS f ON att.file_id=f.file_id
    WHERE att.file_id='.$fileID.' AND att.biblio_id='.$biblioID.' AND att.access_type=\'public\'';
$file_q = $dbs->query($sql_q);
$file_d = $file_q->fetch_assoc();

if ($file_q->num_rows > 0) {
    $file_loc = REPO_BASE_DIR.str_ireplace('/', DIRECTORY_SEPARATOR, $file_d['file_dir']).DIRECTORY_SEPARATOR.$file_d['file_name'];
    if (file_exists($file_loc)) {

        if ($file_d['access_limit']) {
            if (utility::isMemberLogin()) {
                $allowed_mem_types = @unserialize($file_d['access_limit']);
                if (!in_array($_SESSION['m_member_type_id'], $allowed_mem_types)) {
                    # Access to file restricted
                    # Member logged in but doesnt have privilege to download
                    header("location:index.php");
                    continue;
                }
            } else {
                # Access to file restricted
                # Member not logged in to download
                header("location:index.php");
                continue;
            }
        }

        if ($file_d['mime_type'] == 'application/pdf') {
            #echo '<h1>PDF</h1>';
            #exec('/var/www/sendev-01/s3st12-test/lib/swftools/bin/pdf2swf -o /var/www/sendev-01/s3st12-test/files/swfs/document.swf '.$file_loc.'');
            #exec('lib/swftools/bin/pdf2swf -o files/swfs/document.swf '.$file_loc.'');
            #exec('lib/swftools/bin/pdf2swf -o files/swfs/'.basename($file_loc).'.swf '.$file_loc.'');
            $swf = basename($file_loc);
            $swf = sha1($swf);
            $swf = $swf.'.swf';
            if (!file_exists('files/swfs/'.$swf.'')) {
                exec('lib/swftools/bin/pdf2swf -o files/swfs/'.$swf.' '.$file_loc.'');
            }
            header("location:?p=readpdf&swf=".$swf."");
        } else {
            header('Content-Disposition: inline; filename="'.basename($file_loc).'"');
            header('Content-Type: '.$file_d['mime_type']);
            readfile($file_loc);
            exit();
        }

    } else {
        die('<div class="errorBox">File Not Found!</div>');
    }
} else {
    die('<div class="errorBox">File Not Found!</div>');
}
?>
