# wahelp_tz


## init
```bash
git clone https://github.com/weidali/wahelp_tz.git
cd wahelp_tz

composer install
#copy env and set db connection credentials
cp .env.example .env

cd public
php -S localhost:8088
```


## api
<details>
 <summary><code>GET</code> <code><b>/</b></code> <code>Get All users</code></summary>

##### Parameters
> `none`

##### Responses
> | http code     | content-type                      | response                                                            |
> |---------------|-----------------------------------|---------------------------------------------------------------------|
> | `200`         | `application/json`                | `[{"id": 1,"number": "978978978",}, ...]`                           |
> | `404`         | `application/json`                | `{"success":false,"message":"Route not found"}`                     |

##### Example cURL
> ```bash
>  curl -X GET -H "Content-Type: application/json" http://localhost:8088/
> ```
</details>
<details>
 <summary><code>POST</code> <code><b>/upload</b></code> <code>Upload CSV file</code></summary>

##### Parameters 
> | name        |  type      | data type      | description                                          |
> |-------------|------------|----------------|------------------------------------------------------|
> | `file`      |  required  | file           | CSV file                                             |

##### Responses
> | http code     | content-type                      | response                                                             |
> |---------------|-----------------------------------|----------------------------------------------------------------------|
> | `200`         | `application/json`                | `{"inserted":<COUNT>,"errors":<COUNT>}`                              |
> | `400`         | `application/json`                | `{"success":false,"message":"File upload failed","error_code":null}` |
> | `500`         | `application/json`                | `{"success":false,"message":"Failed to save file","error_code":null}`|

##### Example cURL
> ```bash
> curl -X POST -F "file=@path/to/csv/file.csv" http://localhost:8088/upload
> ```
</details>
<details>
 <summary><code>GET</code> <code><b>/notify</b></code> <code>Fake Notification Service (api just for test)</code></summary>

##### Parameters 
> `none`

##### Responses
> | http code     | content-type                      | response                                                             |
> |---------------|-----------------------------------|----------------------------------------------------------------------|
> | `200`         | `application/json`                | `{"success":true,"message":"Notification process completed.","data":{"notification_id":11,"sent":10001,"errors":0}}` |

##### Example cURL
> ```bash
> curl -X GET -H "Content-Type: application/json" http://localhost:8088/notify
> ```
</details>


## preview
### /
[![main|200x150](https://i.postimg.cc/1tWjbwcF/temp-Image-OJUf-E9.avif)](https://postimg.cc/k6bsbVHM)

### /upload
[![upload|200x150](https://i.postimg.cc/zBPxx58S/temp-Image72jcf-D.avif)](https://postimg.cc/tZWdgK1Y)

### /notify
[![notify|200x150](https://i.postimg.cc/nhkC8hgx/temp-Imagez-USWZk.avif)](https://postimg.cc/tssXWp9v)
