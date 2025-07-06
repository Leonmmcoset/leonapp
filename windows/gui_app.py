import tkinter as tk
from tkinter import ttk, messagebox, filedialog
import requests
import json
import threading

class AppStoreGUI:
    def __init__(self, root):
        self.root = root
        self.root.title("应用商店")
        self.root.geometry("800x600")
        self.root.resizable(True, True)

        # 创建搜索框架
        self.search_frame = ttk.Frame(root)
        self.search_frame.pack(fill=tk.X, padx=10, pady=5)

        ttk.Label(self.search_frame, text="搜索应用: ").pack(side=tk.LEFT, padx=5)
        self.search_entry = ttk.Entry(self.search_frame, width=50)
        self.search_entry.pack(side=tk.LEFT, padx=5)
        ttk.Button(self.search_frame, text="搜索", command=self.on_search).pack(side=tk.LEFT, padx=5)

        # 创建主框架
        self.main_frame = ttk.Frame(root)
        self.main_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=5)

        # 创建滚动条
        self.canvas = tk.Canvas(self.main_frame)
        self.scrollbar = ttk.Scrollbar(self.main_frame, orient="vertical", command=self.canvas.yview)
        self.scrollable_frame = ttk.Frame(self.canvas)

        self.scrollable_frame.bind(
            "<Configure>",
            lambda e: self.canvas.configure(
                scrollregion=self.canvas.bbox("all")
            )
        )

        self.canvas.create_window((0, 0), window=self.scrollable_frame, anchor="nw")
        self.canvas.configure(yscrollcommand=self.scrollbar.set)

        self.canvas.pack(side="left", fill="both", expand=True)
        self.scrollbar.pack(side="right", fill="y")

        # 加载应用列表
        self.load_apps()

    def fetch_apps(self, search_term=None):
        url = 'http://localhost:3232/api.php?action=list'
        if search_term:
            url += f'&search={search_term}'
        try:
            response = requests.get(url)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            messagebox.showerror('错误', f'获取应用列表失败: {str(e)}')
            return []

    def fetch_app_details(self, app_id):
        url = f'http://localhost:3232/api.php?action=app&id={app_id}'
        try:
            response = requests.get(url)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            messagebox.showerror('错误', f'获取应用详情失败: {str(e)}')
            return None

    def download_version(self, version_id):
        url = f'http://localhost:3232/api.php?action=download&version_id={version_id}'
        try:
            response = requests.get(f'http://localhost:3232/api/app/{app_id}')
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            messagebox.showerror("错误", f"获取应用详情失败: {str(e)}")
            return None

    def on_search(self):
        # 清空现有应用卡片
        for widget in self.scrollable_frame.winfo_children():
            widget.destroy()
        # 加载搜索结果
        search_term = self.search_entry.get().strip()
        self.load_apps(search_term)

    def load_apps(self, search_term=None):
        apps = self.fetch_apps(search_term)
        if not apps:
            ttk.Label(self.scrollable_frame, text="没有找到应用程序").pack(pady=20)
            return

        # 创建应用卡片网格
        for i, app in enumerate(apps):
            frame = ttk.LabelFrame(self.scrollable_frame, text=app.get("name"))
            frame.grid(row=i//2, column=i%2, padx=10, pady=10, sticky="nsew")

            ttk.Label(frame, text=f"描述: {app.get('description', '无')}").pack(anchor="w", padx=5, pady=2)
            ttk.Label(frame, text=f"评分: {app.get('avg_rating', '暂无')}/5").pack(anchor="w", padx=5, pady=2)
            ttk.Label(frame, text=f"适用平台: {','.join(app.get('platforms', []))}").pack(anchor="w", padx=5, pady=2)
            ttk.Button(frame, text="查看详情", command=lambda a=app: self.show_details(a)).pack(pady=5)

        # 配置网格权重使卡片自适应
        self.scrollable_frame.grid_columnconfigure(0, weight=1)
        self.scrollable_frame.grid_columnconfigure(1, weight=1)

    def show_details(self, app):
        # 获取完整应用详情
        app_details = self.fetch_app_details(app['id'])
        if not app_details:
            return

        detail_window = tk.Toplevel(self.root)
        detail_window.title(app_details.get("name"))
        detail_window.geometry("600x400")

        # 创建滚动区域
        canvas = tk.Canvas(detail_window)
        scrollbar = ttk.Scrollbar(detail_window, orient="vertical", command=canvas.yview)
        scrollable_frame = ttk.Frame(canvas)

        scrollable_frame.bind(
            "<Configure>",
            lambda e: canvas.configure(
                scrollregion=canvas.bbox("all")
            )
        )

        canvas.create_window((0, 0), window=scrollable_frame, anchor="nw")
        canvas.configure(yscrollcommand=scrollbar.set)

        canvas.pack(side="left", fill="both", expand=True)
        scrollbar.pack(side="right", fill="y")

        # 应用基本信息
        ttk.Label(scrollable_frame, text=f"名称: {app_details.get('name')}", font=('Arial', 12, 'bold')).pack(anchor="w", padx=10, pady=5)
        ttk.Label(scrollable_frame, text=f"描述: {app_details.get('description', '无')}").pack(anchor="w", padx=10, pady=5)
        ttk.Label(scrollable_frame, text=f"评分: {app_details.get('avg_rating', '暂无')}/5").pack(anchor="w", padx=10, pady=5)
        ttk.Label(scrollable_frame, text=f"适用年龄: {app_details.get('age_rating', '未知')}").pack(anchor="w", padx=10, pady=5)
        ttk.Label(scrollable_frame, text=f"适用平台: {','.join(app_details.get('platforms', []))}").pack(anchor="w", padx=10, pady=5)

        # 版本信息
        versions = app_details.get('versions', [])
        if versions:
            ttk.Label(scrollable_frame, text="\n=== 版本列表 ===", font=('Arial', 10, 'bold')).pack(anchor="w", padx=10, pady=10)
            for version in versions:
                version_frame = ttk.Frame(scrollable_frame)
                version_frame.pack(anchor="w", padx=15, pady=5, fill=tk.X)

                ttk.Label(version_frame, text=f"版本 {version.get('version_name', '未知')}", font=('Arial', 9, 'bold')).pack(anchor="w")
                ttk.Label(version_frame, text=f"发布日期: {version.get('created_at', '未知')}").pack(anchor="w")
                ttk.Label(version_frame, text=f"文件大小: {version.get('file_size', '未知')}").pack(anchor="w")
                ttk.Button(version_frame, text="下载", command=lambda v=version: self.download_version(v)).pack(anchor="e", pady=5)

        ttk.Button(scrollable_frame, text="关闭", command=detail_window.destroy).pack(pady=20)

    def download_version(self, version):
        def download_thread():
            try:
                # 询问保存路径
                save_path = filedialog.asksaveasfilename(
                    defaultextension=".apk",
                    filetypes=[("APK files", "*.apk"), ("All files", "*")],
                    initialfile=version.get('file_name', f"app_{version['id']}.apk")
                )
                if not save_path:
                    return

                # 下载文件
                response = requests.get(f'http://localhost:3232/api/download/{version["id"]}', stream=True)
                response.raise_for_status()

                total_size = int(response.headers.get('content-length', 0))
                block_size = 1024  # 1 KB
                progress = 0

                with open(save_path, 'wb') as file:
                    for data in response.iter_content(block_size):
                        progress += len(data)
                        file.write(data)
                        # 更新进度条
                        progress_var.set((progress / total_size) * 100)
                        root.update_idletasks()

                messagebox.showinfo("成功", f"文件已下载至:\n{save_path}")
            except requests.exceptions.RequestException as e:
                messagebox.showerror("错误", f"下载失败: {str(e)}")
            finally:
                progress_window.destroy()

        # 创建进度窗口
        progress_window = tk.Toplevel(self.root)
        progress_window.title(f"下载版本 {version.get('version_name', '未知')}")
        progress_window.geometry("300x100")
        progress_window.resizable(False, False)

        ttk.Label(progress_window, text="下载中...").pack(pady=10)
        progress_var = tk.DoubleVar()
        progress_bar = ttk.Progressbar(progress_window, variable=progress_var, maximum=100)
        progress_bar.pack(fill=tk.X, padx=20, pady=10)

        # 启动下载线程
        threading.Thread(target=download_thread).start()

if __name__ == "__main__":
    root = tk.Tk()
    app = AppStoreGUI(root)
    root.mainloop()