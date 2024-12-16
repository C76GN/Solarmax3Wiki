import os
import sys

# 检查是否提供了目录参数
if len(sys.argv) != 2:
    print("用法: python add_comment.py <目录路径>")
    sys.exit(1)

# 将第一个参数赋值给变量
directory = sys.argv[1]

# 打印检查的目录
print(f"检查的目录: {directory}")

# 检查目录是否存在
if not os.path.isdir(directory):
    print("指定的目录不存在，请检查路径。")
    sys.exit(1)

# 查找要处理的文件
files = []
for root, _, filenames in os.walk(directory):
    for filename in filenames:
        if filename.endswith(('.php', '.vue', '.js')):
            files.append(os.path.join(root, filename))

# 检查是否找到文件
if not files:
    print("在指定目录中未找到符合条件的文件。")
    sys.exit(0)

# 显示要处理的文件列表
print("以下文件将添加注释：")
for file in files:
    print(file)

confirmation = input("是否继续添加注释？(输入 yes 继续，输入 no 取消，回车确认): ")

# 根据用户输入决定是否添加注释
if confirmation.lower() == 'yes' or confirmation == '':
    for file in files:
        comment = f"// FileName: {file}\n"
        
        # 使用文件操作将注释添加到文件开头
        with open(file, 'r') as f:
            content = f.read()
        
        if file.endswith('.php'):
            # 对于 PHP 文件，确保注释在 <?php 后面
            if content.startswith('<?php'):
                # 找到 <?php 的位置
                php_pos = content.index('<?php') + len('<?php')
                # 提取 <?php 后的第一行
                first_line = content[php_pos:].strip().split('\n')[0]
                # 检查第一行是否已经存在相同的注释
                if first_line.strip() == comment.strip():
                    print(f"跳过文件 {file}，注释已存在且相同。")
                    continue
                # 如果没有注释，插入新的注释
                new_content = content[:php_pos] + '\n' + comment + content[php_pos:]
            else:
                # 如果没有 <?php，直接在开头插入注释
                new_content = comment + content
        else:
            # 对于其他文件，检查是否已经存在相同的注释
            if content.startswith(comment.strip()):
                print(f"跳过文件 {file}，注释已存在且相同。")
                continue
            else:
                # 直接在开头插入注释
                new_content = comment + content

        with open(file, 'w') as f:
            f.write(new_content)

    print("注释已成功添加到文件开头。")
else:
    print("操作已取消。")
