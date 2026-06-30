function acceptWork(workId) {
            if (confirm('确定要接这个任务吗？')) {
                // 这里可以换成 AJAX 提交
                //alert('接单功能开发中...');
                const request = new XMLHttpRequest();
                request.open('POST', 'api/accept_work.php');
                var data = new FormData();
                data.append('work_id', workId);
                request.send(data)
                request.onload = function()
                {
                    if (request.status === 200)
                    {
                        var json = JSON.parse(request.responseText);
                        if (json.status === 'success')
                        {
                            alert(json.message);
                            location.reload();
                        }
                        else
                        {
                            alert(json.message);
                        }        
                    }
                }
            }
        }