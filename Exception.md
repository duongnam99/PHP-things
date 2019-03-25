# Xử lí ngoại lệ trong PHP
## 1. PHP Exception
- PHP Exception (ngoại lệ trong PHP) là phương pháp dùng để quản lý, xử lý lỗi trong PHP. 
- Exception sử dụng cho việc chủ động bắt lỗi trên phần code mà chúng ta muốn. Sau đó quy tụ các lỗi vào một nơi để dễ dàng quản lý và xử lý chúng.
- PHP cung cấp class `Exception`, với các phương thức được đinh nghĩa sẵn: `getMessage`, `getCode`, `getFile`, `getLine`, `getTrace`, `getPrevious`, `getTraceAsString`, đa phần xuất hiện từ bản 5.1.0, với `Error` class thì xuất hiện từ bản 7.0 trở đi.
## 2. Try - Throw - Catch - Finally
- `Try` – Sử dụng hàm try{...} bên trong là phần code chúng ta muốn kiểm tra. Nếu không có lỗi thì đoạn code vẫn hoạt động bình thường
- `Throw` – Đây là câu lệnh thường được gọi là ném lỗi, khi Throw được kích hoạt nó sẽ tìm đến hàm Catch (nếu có hàm Throw thì phải có hàm Catch đi kèm!).
- `Catch` – Hàm này dùng để lấy lỗi từ hàm Throw, các lỗi được ném ra sẽ quy tụ tại đây.
    - Hàm Catch() khởi tạo một object để lưu thông tin lỗi, ví dụ: 
    ```
    catch(Exception $e) {
        echo 'Message: ' .$e->getMessage();
    }
    ```
- `Finally`: thực thi sau try - catch  

## 3. Custom Ecxeption
```
<?php
class customException extends Exception {
  public function errorMessage() {
    // Thông báo lỗi
    $errorMsg = 'Error on line '.$this->getLine().' in '.$this->getFile()
    .': <b>'.$this->getMessage().'</b> is not a valid E-Mail address';
    return $errorMsg;
  }
}
 
$email = "someone@example.com";
 
try {
  // Kiểm tra email có hợp lệ hay không
  if(filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
    // Ném ra một lỗi nếu email không đúng
    throw new customException($email);
  }
  // Kiểm tra có từ "example" trong email hay không
  if(strpos($email, "example") !== FALSE) {
    // Nếu có từ "example" thì ném ra một lỗi
    throw new Exception("$email là một email dùng để ví dụ");
  }
}
 
catch (customException $e) {
  echo $e->errorMessage();
}
 
catch(Exception $e) {
  echo $e->getMessage();
}
?>
```
- Nếu có là lỗi thì throw new exception và catch sẽ bắt exception tương ứng cới object throw ra.