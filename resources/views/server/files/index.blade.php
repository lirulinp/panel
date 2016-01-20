{{--
    Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
--}}
@extends('layouts.master')

@section('title')
    Managing Files for: {{ $server->name }}
@endsection

@section('content')
<div class="col-md-12">
    <div class="row">
        <div class="col-md-12" id="internal_alert">
            <div class="alert alert-info">
                <i class="fa fa-spinner fa-spin"></i> {{ trans('server.files.loading') }}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="ajax_loading_box"><i class="fa fa-refresh fa-spin" id="position_me"></i></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12" id="load_files"></div>
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">File Path Information</h3>
                </div>
                <div class="panel-body">
                    When configuring any file paths in your server plugins or settings you should use <code>/home/container</code> as your base path. While your SFTP client sees the files as <code>/public</code> this is not true for the server process.
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        $('.server-files').addClass('active');
    });
    $(window).load(function(){
        var doneLoad = false;

        // Show Loading Animation
        function handleLoader (show) {

            // Hide animation if no files displayed.
            if ($('#load_files').height() < 5) { return; }

            // Show Animation
            if (show === true){
                var height = $('#load_files').height();
                var width = $('.ajax_loading_box').width();
                var center_height = (height / 2) - 30;
                var center_width = (width / 2) - 30;
                $('#position_me').css({
                    'top': center_height,
                    'left': center_width,
                    'font-size': '60px'
                });
                $(".ajax_loading_box").css('height', (height + 5)).fadeIn();
            } else {
                $('.ajax_loading_box').fadeOut(100);
            }

        }

        function reloadActions () {
            reloadActionClick();
            reloadActionDelete();
        }

        // Handle folder clicking to load new contents
        function reloadActionClick () {
            $('a.load_new').click(function (e) {
                e.preventDefault();
                window.history.pushState(null, null, $(this).attr('href'));
                loadDirectoryContents($.urlParam('dir', $(this).attr('href')));
            });
        }

        // Handle Deleting Files
        function reloadActionDelete () {
            $('a.delete_file').click(function (e) {
                e.preventDefault();
                var clicked = $(this);
                var deleteItemPath = $(this).attr('href');

                swal({
                    type: 'warning',
                    title: 'Really Delete this File?',
                    showCancelButton: true,
                    showConfirmButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true
                }, function () {
                    $.ajax({
                        type: 'DELETE',
                        url: '{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/file/' + deleteItemPath,
                        headers: {
                            'X-Access-Token': '{{ $server->daemonSecret }}',
                            'X-Access-Server': '{{ $server->uuid }}'
                        }
                    }).done(function (data) {
                        clicked.parent().parent().parent().parent().fadeOut();
                        swal({
                            type: 'success',
                            title: 'File Deleted'
                        });
                    }).fail(function (jqXHR) {
                        console.error(jqXHR);
                        swal({
                            type: 'error',
                            title: 'Whoops!',
                            html: true,
                            text: 'An error occured while attempting to delete this file. Please try again.',
                        });
                    });
                });

            });
        }

        // Handle Loading Contents
        function loadDirectoryContents (dir) {

            handleLoader(true);
            var outputContent;
            var urlDirectory = (dir === null) ? '/' : dir;

            $.ajax({
                type: 'POST',
                url: '{{ route('server.files.directory-list', $server->uuidShort) }}',
                headers: { 'X-CSRF-Token': '{{ csrf_token() }}' },
                data: { directory: urlDirectory }
            }).done(function (data) {
                handleLoader(false);
                $("#load_files").slideUp(function () {
                    $("#load_files").html(data).slideDown();
                    $('[data-toggle="tooltip"]').tooltip();
                    $('#internal_alert').slideUp();

                    // Run Actions Again
                    reloadActions();
                });
            }).fail(function (jqXHR) {
                $("#internal_alert").html('<div class="alert alert-danger">An error occured while attempting to process this request. Please try again.</div>').show();
                console.log(jqXHR);
            });

        }

        // Load on Initial Page Load
        loadDirectoryContents($.urlParam('dir'));

    });
</script>
@endsection
