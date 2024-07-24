关于excel表格文件的相关操作

导出： 推荐使用csv
导入： 需要判断导入文件的具体类型

若无法确定 可使用 try catch 切换尝试

导入推荐优先使用xlsx
导出也可统一使用xlsx

推荐使用：
读xlsx SimpleXLSX
读xls  SimpleXLS
写xlsx SimpleXLSXGen
具体用法参考 类的注释说明