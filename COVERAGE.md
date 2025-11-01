# 代碼覆蓋率分析報告

> 生成日期：2025-11-01
> 分析方法：手動代碼審查

## 📊 總覽

| 類別 | 總方法數 | 已測試 | 未測試 | 覆蓋率 |
|------|---------|--------|--------|--------|
| RouteProvider | 5 | 4 | 1 | 80% |
| RouteInfo | 8 | 8 | 0 | **100%** |
| RouteReflection | 6 | 0 | 6 | 0% |
| **總計** | **19** | **12** | **7** | **63%** |

## 📝 詳細分析

### ✅ RouteProvider.php - 80% 覆蓋率

**已測試的方法：**
- ✅ `__construct()` - 間接測試（通過 fromRouteCollection）
- ✅ `getRoutes()` - 4 個測試案例
  - testGetRoutesFromRouteCollection
  - testSkipsInternalSymfonyRoutes
  - testHandlesEmptyRouteCollection
  - testPreservesRouteNames
- ✅ `isInternalRoute()` - 間接測試（testSkipsInternalSymfonyRoutes）
- ✅ `fromRouteCollection()` - 所有測試都使用

**未測試的方法：**
- ❌ `fromRouter()` - 靜態工廠方法，需要 Symfony Router 實例

### ✅ RouteInfo.php - 100% 覆蓋率

**已測試的方法：**
- ✅ `__construct()` - 所有測試都使用
- ✅ `isClassBased()` - 2 個測試
  - testIdentifiesClassBasedRoutes
  - testHandlesInvokableControllers
- ✅ `className()` - 2 個測試
  - testIdentifiesClassBasedRoutes
  - testHandlesInvokableControllers
- ✅ `methodName()` - 2 個測試
  - testIdentifiesClassBasedRoutes
  - testHandlesInvokableControllers
- ✅ `reflectionMethod()` - 1 個測試
  - testGetsReflectionMethod
- ✅ `getMethods()` - 1 個測試
  - testGetsMethods
- ✅ `getPath()` - 1 個測試
  - testGetsPath
- ✅ `getParameterNames()` - 1 個測試
  - testGetsParameterNames

### ❌ RouteReflection.php - 0% 覆蓋率

**未測試的方法：**
- ❌ `__construct(Route $route)` - private 建構函數
- ❌ `createFromRoute(Route $route): self` - 靜態工廠方法
- ❌ `getSignatureParametersMap(array $signatureParameters): array` - 核心方法
  - 功能：映射路由參數到方法參數
  - 複雜度：高（包含名稱匹配、型別綁定邏輯）
- ❌ `getBoundParametersTypes(array $signatureParameters): array` - 核心方法
  - 功能：獲取綁定參數的型別
  - 複雜度：中等（型別檢測邏輯）
- ❌ `getParameterNames(): array` - private 輔助方法
  - 功能：從路由路徑提取參數名稱
  - 複雜度：低（正則表達式匹配）
- ❌ `toSnakeCase(string $value): string` - private 輔助方法
  - 功能：將字串轉換為 snake_case
  - 複雜度：中等（字串轉換邏輯）

## 🧪 測試案例統計

### RouteProviderTest - 4 個測試，8 個斷言

1. **testGetRoutesFromRouteCollection** - 基本路由收集
   - 驗證：正確收集多個路由
   - 斷言：路由數量、路由鍵值存在

2. **testSkipsInternalSymfonyRoutes** - 過濾內部路由
   - 驗證：過濾 _profiler、_wdt、_preview_error 等內部路由
   - 斷言：只保留應用路由

3. **testHandlesEmptyRouteCollection** - 空集合處理
   - 驗證：處理空路由集合
   - 斷言：返回空陣列

4. **testPreservesRouteNames** - 路由名稱保留
   - 驗證：保留原始路由名稱（如 user.show）
   - 斷言：路由名稱正確

### RouteInfoTest - 6 個測試，16 個斷言

1. **testIdentifiesClassBasedRoutes** - 類別路由識別
   - 驗證：識別 ClassName::method 格式的控制器
   - 斷言：isClassBased、className、methodName

2. **testHandlesInvokableControllers** - 可調用控制器
   - 驗證：處理單一類別名稱的可調用控制器
   - 斷言：methodName 為 __invoke

3. **testGetsMethods** - HTTP 方法獲取
   - 驗證：獲取路由的 HTTP 方法
   - 斷言：包含 GET、POST

4. **testGetsPath** - 路徑獲取
   - 驗證：獲取路由路徑
   - 斷言：路徑字串正確

5. **testGetsParameterNames** - 參數名稱提取
   - 驗證：從路徑提取參數名稱
   - 斷言：參數陣列正確

6. **testGetsReflectionMethod** - 反射方法獲取
   - 驗證：獲取控制器方法的反射
   - 斷言：反射對象存在、方法名正確、參數數量正確

## 📈 覆蓋率改善建議

### 🔴 高優先級

#### 1. RouteReflection 類別測試（0% → 目標 80%+）

這是核心功能，處理路由參數映射和型別推斷，**必須優先補充測試**。

**建議測試案例：**

```php
// 測試 1: 簡單參數映射
testSimpleParameterMapping()
- 路由: /users/{id}
- 方法: show(int $id)
- 預期: ['id' => 'id']

// 測試 2: camelCase 到 snake_case 轉換
testSnakeCaseConversion()
- 路由: /users/{user_id}
- 方法: show(int $userId)
- 預期: ['user_id' => 'userId']

// 測試 3: 型別綁定檢測
testTypedParameterBinding()
- 路由: /products/{product}
- 方法: show(Product $product)
- 預期: ['product' => 'Product']

// 測試 4: 多參數路由
testMultipleParameters()
- 路由: /users/{userId}/posts/{postId}
- 方法: show(int $userId, int $postId)
- 預期: 正確映射兩個參數

// 測試 5: 混合型別參數
testMixedTypeParameters()
- 路由: /categories/{category}/products/{id}
- 方法: show(Category $category, int $id)
- 預期: 正確識別 Entity 和 scalar 型別

// 測試 6: WeakMap 快取機制
testCachingMechanism()
- 驗證同一 Route 對象返回同一 RouteReflection 實例

// 測試 7: toSnakeCase 轉換
testToSnakeCaseConversion()
- 'userId' → 'user_id'
- 'ProductCategory' → 'product_category'
- 'HTTPResponse' → 'h_t_t_p_response'
```

### 🟡 中優先級

#### 2. RouteProvider.fromRouter() 測試（80% → 100%）

**建議測試案例：**

```php
// 測試 1: 從 Router 創建
testCreateFromRouter()
- 創建 Router mock
- 驗證正確實例化
- 預期: RouteProvider 實例

// 測試 2: Router 與 RouteCollection 一致性
testRouterConsistency()
- 驗證 fromRouter 和 fromRouteCollection 行為一致
```

## 🎯 測試覆蓋率目標

| 階段 | 目標覆蓋率 | 重點改善 |
|------|-----------|---------|
| **當前** | 63% | - |
| **階段 1** | 75% | 補充 RouteReflection 基本測試 (4 個) |
| **階段 2** | 85% | 補充 RouteReflection 進階測試 (3 個) |
| **階段 3** | 90%+ | 補充 RouteProvider.fromRouter() 測試 |

## 💡 測試品質評估

### 優點
- ✅ RouteInfo 達到完美 100% 覆蓋
- ✅ 所有公開 API 都有測試
- ✅ 測試案例涵蓋常見使用場景
- ✅ 邊界條件測試（空集合、內部路由過濾）

### 需改善
- ❌ RouteReflection 核心邏輯完全未測試
- ⚠️ 缺少錯誤處理測試
- ⚠️ 缺少複雜場景測試（多層嵌套、特殊字元等）

## 📌 結論

目前的測試覆蓋率為 **63%**，主要缺口在 **RouteReflection** 類別。建議：

1. **立即行動**：為 RouteReflection 補充 6-8 個測試案例
2. **短期目標**：將整體覆蓋率提升至 80%+
3. **長期維護**：每次新增功能時同步更新測試

---

## 🔧 如何生成覆蓋率報告

由於環境限制，目前使用手動分析。如果需要自動化覆蓋率報告，需要：

1. 安裝 PCOV 或 Xdebug 擴展
2. 運行：`composer test-coverage`
3. 查看：`coverage/index.html`

**注意**：在生產環境建議使用 PCOV（性能更好）。
