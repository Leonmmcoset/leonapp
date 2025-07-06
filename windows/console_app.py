import requests
import json

def fetch_apps(search_term=None):
    url = 'http://localhost:3232/api.php?action=list'
    if search_term:
        url += f'&search={search_term}'
    try:
        response = requests.get(url)
        response.raise_for_status()
        return response.json()
    except requests.exceptions.RequestException as e:
        print(f"获取应用列表失败: {e}")
        return []

def get_app_details(app_id):
    url = f'http://localhost:3232/api.php?action=app&id={app_id}'
    try:
        response = requests.get(url)
        response.raise_for_status()
        return response.json()
    except requests.exceptions.HTTPError as e:
        if response.status_code == 404:
            try:
                error_data = response.json()
                print(f"获取应用详情失败: {error_data.get('error')}")
                print(f"执行的SQL: {error_data.get('sql')}")
            except ValueError:
                print(f"获取应用详情失败: {e}")
        else:
            print(f"获取应用详情失败: {e}")
        return None
    except requests.exceptions.RequestException as e:
        print(f"获取应用详情失败: {e}")
        return None

def download_app(version_id):
    url = f'http://localhost:3232/api.php?action=download&version_id={version_id}'
    try:
        response = requests.get(f'http://localhost:3232/api/download/{version_id}', stream=True)
        response.raise_for_status()
        
        filename = response.headers.get('Content-Disposition', '').split('filename=')[-1].strip('"')
        if not filename:
            filename = f'app_version_{version_id}.apk'
        
        with open(filename, 'wb') as f:
            for chunk in response.iter_content(chunk_size=8192):
                f.write(chunk)
        
        print(f'下载成功: {filename}')
        return True
    except requests.exceptions.RequestException as e:
        print(f'下载失败: {e}')
        return False

def display_apps(apps):
    if not apps:
        print('没有找到应用程序')
        return

    print('=== 应用商店 ===')
    for i, app in enumerate(apps, 1):
        print(f'[{i}] {app.get("name")}')
        print(f'   描述: {app.get("description", "无")}')
        print(f'   评分: {app.get("avg_rating", "暂无")}/5')
        print(f'   适用平台: {", ".join(app.get("platforms", []))}')

if __name__ == "__main__":
    while True:
        print("\n=== 应用商店控制台 ===")
        print("1. 浏览所有应用")
        print("2. 搜索应用")
        print("3. 查看应用详情")
        print("4. 下载应用")
        print("5. 退出")
        
        choice = input("请选择操作 (1-5): ")
        
        if choice == "1":
            apps = fetch_apps()
            display_apps(apps)
        elif choice == "2":
            search_term = input("请输入搜索关键词: ")
            apps = fetch_apps(search_term)
            display_apps(apps)
        elif choice == "3":
            app_id = input("请输入应用ID: ")
            app = get_app_details(app_id)
            if app:
                print("\n=== 应用详情 ===")
                print(f"名称: {app.get('name')}")
                print(f"描述: {app.get('description', '无')}")
                print(f"评分: {app.get('avg_rating', '暂无')}/5")
                print(f"适用平台: {', '.join(app.get('platforms', []))}")
                print("版本:")
                for version in app.get('versions', []):
                    print(f"  - {version.get('version_name')} (ID: {version.get('id')})")
            else:
                print("应用不存在或获取失败")
        elif choice == "4":
            version_id = input("请输入版本ID: ")
            download_app(version_id)
        elif choice == "5":
            print("谢谢使用，再见！")
            break
        else:
            print("无效的选择，请重试")